/**
 * Comfino payment method renderer for Luma checkout theme
 *
 * Loads the Comfino SDK via native dynamic import() and passes paywall data to bootstrapPaywall().
 * The SDK handles all paywall lifecycle logic (init, iframe, offer selection) via MagentoPaywallController.
 */
define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/error-processor',
    'mage/url',
    'Magento_Customer/js/customer-data'
], function (Component, quote, storage, fullScreenLoader, errorProcessor, url, customerData) {
    'use strict';

    /* Cache the SDK-load promise on window so repeated KO re-mounts share a single fetch. */
    let cachedSdk = null;

    function loadComfinoSdk(cfg)
    {
        if (window.__comfinoSdkPromise) {
            return window.__comfinoSdkPromise;
        }

        window.__comfinoSdkPromise = import(cfg.sdkScriptUrl)
            .then(function (ns) {
                cachedSdk = ns;

                return ns;
            })
            .catch(function (error) {
                window.__comfinoSdkPromise = null;

                return Promise.reject(error);
            });

        return window.__comfinoSdkPromise;
    }

    function resolveComfinoSdk()
    {
        return cachedSdk;
    }

    return Component.extend({
        defaults: {
            template: 'Comfino_ComfinoGateway/payment/comfino',
            redirectAfterPlaceOrder: false
        },

        initialize: function () {
            this._super();

            const config = (window.checkoutConfig.payment || {}).comfino || {};

            /* Standard-rendering Comfino logo placeholder: a static CDN SVG the KO template binds via getDefaultLogoUrl(),
               marked data-comfino-logo so the SDK's DefaultPaymentMethodItemRenderer adopts this same <img> and swaps
               its src to the auth API logo. Plain component method (not an observable) — the value never changes after
               mount, matching getTitle()/getCode(). */
            this.defaultLogoUrl = config.defaultLogoUrl || '';

            // allowedProductTypes: null = no filter active, [] = all filtered (don't load SDK),
            // [...] = filtered subset to pass to bootstrapPaywall().
            if (Array.isArray(config.allowedProductTypes) && config.allowedProductTypes.length === 0) {
                // All product types filtered out for this cart — don't load the paywall.
                return this;
            }

            /* Read live totals from the quote model — these include shipping chosen on the previous step, unlike
               window.checkoutConfig, which is frozen at page load time (before shipping is selected). Patch the
               server-built cart object so the paywall receives current totals/delivery values. */
            const totals = quote.totals();
            const loanAmount = totals ? Math.round((totals.grand_total || 0) * 100) : (config.loanAmount || 0);
            const cart = config.cart ? Object.assign({}, config.cart) : null;

            if (cart) {
                cart.totalAmount = loanAmount;

                if (totals) {
                    const shippingTaxAmount = Math.round((totals.shipping_tax_amount || 0) * 100);

                    cart.deliveryCost = Math.round((totals.shipping_incl_tax || 0) * 100);
                    cart.deliveryNetCost = Math.round((totals.shipping_amount || 0) * 100);
                    cart.deliveryCostVatAmount = shippingTaxAmount;
                    cart.deliveryCostVatRate = cart.deliveryNetCost > 0
                        ? Math.round(shippingTaxAmount / cart.deliveryNetCost * 100)
                        : 0;
                }
            }

            const comfinoPaywallData = {
                authToken: config.authToken || '',
                loggingToken: config.loggingToken || '',
                trackId: config.trackId || '',
                loanAmount: loanAmount,
                environment: config.environment || 'production',
                platform: 'magento',
                loanAmountInputId: 'comfino-loan-amount',
                paywallSettings: config.paywallSettings,
                productTypeNames: config.productTypeNames,
                creditors: config.creditors,
                paymentMethodItem: { auth: config.paymentMethodAuth || '', label: config.paymentMethodLabel || undefined }
            };

            if (cart) {
                comfinoPaywallData.cart = cart;
            }

            if (Array.isArray(config.allowedProductTypes) && config.allowedProductTypes.length > 0) {
                comfinoPaywallData.productTypes = config.allowedProductTypes;
            }

            /* Keep the hidden #comfino-loan-amount input in sync with quote.totals and notify the SDK on every change.
               quote.totals fires after shipping selection, coupon application, and any other action that mutates the
               cart total — Magento's Knockout-based equivalent of WooCommerce's `updated_checkout` jQuery event.

               Contract with the SDK (MagentoPaywallController): the plugin writes the new grosze amount to the input
               value and dispatches a `comfino:loan-amount-changed` CustomEvent (bubbles, with `detail.amount`). The SDK
               listens at document level, so the subscription survives Knockout re-renders that replace the input node;
               it calls reload() only if the iframe is still attached. We never call reload() directly — keeping the
               trigger inside the SDK so the same input-event contract works for any future theme without plugin-side
               branching. If the loaded SDK predates this contract, the dispatch is a harmless no-op. */
            quote.totals.subscribe(function (newTotals) {
                if (!newTotals) {
                    return;
                }

                const amount = Math.round((newTotals.grand_total || 0) * 100);
                const input = document.getElementById('comfino-loan-amount');

                if (!input || amount <= 0) {
                    return;
                }

                input.value = String(amount);

                input.dispatchEvent(new CustomEvent('comfino:loan-amount-changed', {
                    bubbles: true,
                    detail: { amount: amount }
                }));
            });

            /* Load the SDK eagerly on mount. bootstrapPaywall() returns immediately; MagentoPaywallController uses
               waitForContainer to defer iframe creation until #comfino-paywall-container appears in the DOM (after
               KO renders the template) and the shopper selects Comfino. */
            if (config.sdkScriptUrl) {
                loadComfinoSdk(config).then(function (sdk) {
                    if (sdk && typeof sdk.bootstrapPaywall === 'function') {
                        sdk.bootstrapPaywall(comfinoPaywallData);
                    }
                }).catch(function () {
                    /* script-load failed — leave checkout unaffected */
                });
            }

            return this;
        },

        /** Static CDN URL of the default Comfino logo placeholder, bound by the KO template's <img data-bind>. */
        getDefaultLogoUrl: function () {
            return this.defaultLogoUrl || '';
        },

        /** Called by Magento on order placement to collect payment data.
         *
         * Source of truth is the SDK's BasePaywallController.latestLoanParams: if Knockout remounts the payment
         * template during checkout state transitions after the last iframe payment-state message, the freshly rendered
         * hidden inputs carry no `value` and DOM reads would return empty strings, forcing the backend to fall back to
         * the default product. DOM inputs remain as fallback when the SDK accessor is unavailable. */
        getData: function () {
            const sdk = resolveComfinoSdk();
            const sdkParams = (sdk && sdk.BasePaywallController && typeof sdk.BasePaywallController.getLatestLoanParams === 'function')
                ? sdk.BasePaywallController.getLatestLoanParams()
                : null;
            const loanType = (sdkParams && sdkParams.loanType) || (document.getElementById('comfino-loan-type') || {}).value || '';
            const loanTerm = (sdkParams && sdkParams.loanTerm) || (document.getElementById('comfino-loan-term') || {}).value || '';

            return {
                method: this.item.method,
                additional_data: {
                    loanType: loanType,
                    loanTerm: String(loanTerm || '')
                }
            };
        },

        /**
         * Called after Magento order is placed successfully.
         * Sends the order to Comfino API and redirects to the Comfino application URL.
         */
        afterPlaceOrder: function () {
            const self = this;

            fullScreenLoader.startLoader();

            storage.post(url.build('rest/V1/comfino/payment'))
                .done(function (response) {
                    fullScreenLoader.stopLoader();

                    const data = response && response[0];

                    if (data && data.redirectUrl) {
                        window.location.replace(data.redirectUrl);

                        return;
                    }

                    /* Application creation failed server-side: the backend has canceled the orphaned order and restored
                       the source quote, so the cart is preserved. Refresh the cart section to reflect the restored
                       quote, show the error message inline, and re-enable Place Order so the customer can retry instead
                       of landing on a generic failure page. */
                    if (data && data.error) {
                        customerData.reload(['cart'], true);
                        self.messageContainer.addErrorMessage({ message: data.error });
                    }

                    self.isPlaceOrderActionAllowed(true);
                }).fail(function (response) {
                    fullScreenLoader.stopLoader();
                    errorProcessor.process(response, self.messageContainer);
                    self.isPlaceOrderActionAllowed(true);
                });
        }
    });
});
