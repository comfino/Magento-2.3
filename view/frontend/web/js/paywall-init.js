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
        // ComfinoSDK presence is the authoritative signal that the SDK is loaded and the payment
        // method is active.  Exit silently if not set (module disabled, or SDK not yet loaded).
        if (!window.Comfino || !window.Comfino.ComfinoSDK) {
            return;
        }

        var data = window.ComfinoPaywallData;
        if (!data || !data.paywallUrl) {
            console.error('Comfino: window.ComfinoPaywallData.paywallUrl is missing.');
            return;
        }

        // Guard: the Knockout template may not have rendered #comfino-paywall-container yet
        // (e.g. when script.onload fires before KO finishes).  The SDK's PaywallManager throws
        // "Payment method element not found" if neither the container nor the payment radio is
        // in the DOM.  Watch for the container and retry when it appears.
        if (!document.getElementById('comfino-paywall-container')) {
            var observer = new MutationObserver(function () {
                if (document.getElementById('comfino-paywall-container')) {
                    observer.disconnect();
                    init();
                }
            });
            observer.observe(document.body, { childList: true, subtree: true });
            return;
        }

        // Get the SDK singleton, then initialise it (SDK v1 API requires explicit init() call).
        // init() is idempotent — on re-entry it logs a warning and returns without throwing.
        var sdk = window.Comfino.ComfinoSDK.getInstance();
        sdk.init({
            environment: data.environment || 'production',
            platform: 'magento'
        });

        // Destroy previous instance on re-initialization (e.g. SPA navigation)
        if (_paywall) {
            _paywall.destroy();
            _paywall = null;
        }

        // Do NOT pass useObserver — that mode waits for the container via MutationObserver
        // and never fires when the container is already in the DOM (KO checkout case).
        _paywall = sdk.createPaywall({
            paywallUrl:  data.paywallUrl,
            containerId: 'comfino-paywall-container',
            onUpdateOrderPaymentState: function (params) {
                // Store loan parameters in hidden fields; getData() reads them on order placement.
                document.getElementById('comfino-loan-type').value = params.loanType || '';
                document.getElementById('comfino-loan-term').value = params.loanTerm || '';
            },
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
