/**
 * Comfino Paywall Initialization — Magento 2
 * Pure vanilla JavaScript — no Knockout, no RequireJS.
 * Uses Comfino web frontend SDK (window.Comfino.ComfinoSDK).
 * Compatible with Luma, Blank, Hyvä, and headless frontends.
 */
window.ComfinoPaywallInit = (function () {
    'use strict';

    var _paywall = null;

    function init() {
        // window.Comfino.active is set by comfino.phtml only when the payment method is active and the
        // block was rendered. When the module is disabled the flag is absent — exit silently, no SDK check.
        if (!window.Comfino || !window.Comfino.active) {
            return;
        }

        if (!window.Comfino.ComfinoSDK) {
            console.error('Comfino: SDK not loaded — check script tag in template.');
            return;
        }

        var data = window.ComfinoPaywallData;
        if (!data || !data.paywallUrl) {
            console.error('Comfino: window.ComfinoPaywallData.paywallUrl is missing.');
            return;
        }

        var sdk = window.Comfino.ComfinoSDK.getInstance();

        if (!sdk.isInitialized()) {
            sdk.init({
                environment:  data.environment || 'production',
                platformName: 'magento',
                logLevel:     'warn'
            });
        }

        // Destroy previous instance on re-initialization (e.g. SPA navigation)
        if (_paywall) {
            _paywall.destroy();
            _paywall = null;
        }

        _paywall = sdk.createPaywall({
            paywallUrl:  data.paywallUrl,
            containerId: 'comfino-paywall-container',
            useObserver: true,
            onUpdateOrderPaymentState: function (params) {
                // Update hidden form fields read by DataAssignObserver on order placement
                document.getElementById('comfino-loan-amount').value = params.loanAmount || '';
                document.getElementById('comfino-loan-type').value   = params.loanType   || '';
                document.getElementById('comfino-loan-term').value   = params.loanTerm   || '';

                // Persist to backend session for order creation
                var body = 'loan_amount=' + encodeURIComponent(params.loanAmount || '')
                    + '&loan_type=' + encodeURIComponent(params.loanType || '')
                    + '&loan_term=' + encodeURIComponent(params.loanTerm || '');

                fetch(data.paymentStateUrl, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body:    body
                }).catch(function (err) {
                    console.error('Comfino: Failed to update payment state', err);
                });
            },
            onOfferError: function (err) {
                console.error('Comfino paywall error:', err);
            }
        });

        // Reveal the paywall iframe (hidden by default in SDK CSS)
        _paywall.show();
    }

    /**
     * Reload the paywall with a new loan amount (e.g. when cart total changes).
     * Falls back to full re-initialization if sdk.reload() is not available.
     *
     * @param {number} loanAmountGrosze Amount in grosz (1 PLN = 100)
     */
    function reload(loanAmountGrosze) {
        if (!_paywall) {
            return;
        }
        if (typeof _paywall.reload === 'function') {
            _paywall.reload({ loanAmount: loanAmountGrosze });
        } else {
            // Fallback: rebuild with updated URL
            var baseUrl = (window.ComfinoPaywallData.paywallUrl || '').replace(/[?&]loanAmount=\d+/, '');
            var sep = baseUrl.indexOf('?') !== -1 ? '&' : '?';
            window.ComfinoPaywallData.paywallUrl = baseUrl + sep + 'loanAmount=' + loanAmountGrosze;
            _paywall.destroy();
            _paywall = null;
            init();
        }
    }

    function destroy() {
        if (_paywall) {
            _paywall.destroy();
            _paywall = null;
        }
    }

    function initWithObserver() {
        init();

        // MutationObserver for SPA-like checkouts (Hyvä / Alpine.js)
        // Re-initializes if the container re-appears and paywall was lost
        var paymentSection = document.querySelector('[data-method="comfino"]')
            || document.getElementById('comfino-paywall-container');

        if (paymentSection) {
            var observer = new MutationObserver(function () {
                var container = document.getElementById('comfino-paywall-container');
                if (container && !_paywall) {
                    init();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'complete') {
        initWithObserver();
    } else {
        document.addEventListener('readystatechange', function () {
            if (document.readyState === 'complete') {
                initWithObserver();
            }
        });
    }

    return {
        init:             init,
        reload:           reload,
        destroy:          destroy,
        initWithObserver: initWithObserver
    };
}());
