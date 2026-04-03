/**
 * Comfino payment method renderer
 *
 * Injects the Comfino SDK script and passes paywall data directly to bootstrapPaywall() in the
 * onload callback. All paywall lifecycle logic (init, iframe, offer selection) is handled by the
 * SDK via MagentoPaywallController.
 */
define([
    'Magento_Checkout/js/view/payment/default',
    'mage/storage',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/error-processor',
    'mage/url'
], function (Component, storage, fullScreenLoader, errorProcessor, url) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Comfino_ComfinoGateway/payment/comfino',
            redirectAfterPlaceOrder: false
        },

        initialize: function () {
            this._super();

            const config = (window.checkoutConfig.payment || {}).comfino || {};

            // allowedProductTypes: null = no filter active, [] = all filtered (don't load SDK),
            // [...] = filtered subset to pass to bootstrapPaywall().
            const allowedProductTypes = config.allowedProductTypes;

            if (Array.isArray(allowedProductTypes) && allowedProductTypes.length === 0) {
                // All product types filtered out for this cart — don't load the paywall.
                return this;
            }

            const data = {
                authToken: config.authToken || '',
                loanAmount: config.loanAmount || 0,
                environment: config.environment || 'production',
                platform: 'magento'
            };

            if (Array.isArray(allowedProductTypes) && allowedProductTypes.length > 0) {
                data.productTypes = allowedProductTypes;
            }

            // Load SDK as a plain script via DOM injection.
            //
            // Why not require([url])?  The SDK is a UMD bundle.  When RequireJS's global
            // define() is present, UMD takes the AMD branch - it calls define() and returns
            // its export to RequireJS, but skips the global assignment (window.Comfino.*).
            //
            // Solution: hide window.define before the script executes so the SDK's UMD wrapper
            // sees no AMD environment, takes the global-assignment branch, and sets
            // window.Comfino.ComfinoSDK.  Restore define() in onload/onerror.
            // By the time the user can navigate to the payment step all Magento/KO modules are
            // already defined, so the brief window where define is hidden is safe.
            //
            // Pass data directly to bootstrapPaywall() in onload — no global window object used.
            // MagentoPaywallController uses waitForContainer to defer paywall creation until
            // #comfino-paywall-container appears in the DOM (after KO renders the template).
            if (config.sdkScriptUrl && !document.querySelector('script[data-comfino-sdk]')) {
                const _amdDefine = window.define;
                window.define = undefined;

                const script = document.createElement('script');
                script.src = config.sdkScriptUrl;
                script.setAttribute('data-comfino-sdk', '1');
                script.onload = function () {
                    window.define = _amdDefine;
                    window.Comfino.bootstrapPaywall('magento', data);
                };
                script.onerror = function () {
                    window.define = _amdDefine;
                };

                document.head.appendChild(script);
            }

            return this;
        },

        /** Called by Magento on order placement to collect payment data. */
        getData: function () {
            return {
                method: this.item.method,
                additional_data: {
                    loanType: (document.getElementById('comfino-loan-type') || {}).value || '',
                    loanTerm: (document.getElementById('comfino-loan-term') || {}).value || ''
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
                    } else {
                        self.isPlaceOrderActionAllowed(true);
                    }
                }).fail(function (response) {
                    fullScreenLoader.stopLoader();
                    errorProcessor.process(response, self.messageContainer);
                    self.isPlaceOrderActionAllowed(true);
                });
        }
    });
});
