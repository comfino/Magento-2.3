/**
 * Comfino payment method renderer.
 * Bridges Magento's Knockout checkout to the vanilla-JS paywall-init.js IIFE.
 * All paywall UI logic (SDK, iframe, offer selection) lives in paywall-init.js.
 */
define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Comfino_ComfinoGateway/payment/comfino'
        },

        initialize: function () {
            this._super();

            var config = (window.checkoutConfig.payment || {}).comfino || {};
            var self = this;

            // Expose data consumed by paywall-init.js
            window.ComfinoPaywallData = {
                paywallUrl:  config.paywallUrl  || '',
                environment: config.environment || 'production'
            };

            // Load SDK as a plain script via DOM injection.
            //
            // Why not require([url])?  The SDK is a UMD bundle.  When RequireJS's global
            // define() is present, UMD takes the AMD branch — it calls define() and returns
            // its export to RequireJS, but skips the global assignment
            // (window.Comfino.ComfinoSDK = ...).  paywall-init.js needs that global.
            //
            // Why not plain DOM injection?  Appending a <script> while RequireJS is active
            // causes an anonymous define() call that RequireJS didn't initiate → "Mismatched
            // anonymous define()" error that breaks Knockout template rendering.
            //
            // Solution: hide window.define before the script executes so the SDK's UMD wrapper
            // sees no AMD environment, takes the global-assignment branch, and sets
            // window.Comfino.ComfinoSDK.  Restore define() in onload/onerror.
            // By the time the user can navigate to the payment step all Magento/KO modules are
            // already defined, so the brief window where define is hidden is safe.
            //
            // Two cooperating entry points handle all load-vs-click orderings:
            //   script.onload       → SDK ready first, user may already have selected Comfino
            //   isChecked.subscribe → user selects first, SDK may still be loading
            if (config.sdkScriptUrl && !document.querySelector('script[data-comfino-sdk]')) {
                var _amdDefine = window.define;
                window.define = undefined;

                var script = document.createElement('script');
                script.src = config.sdkScriptUrl;
                script.setAttribute('data-comfino-sdk', '1');
                script.onload = function () {
                    window.define = _amdDefine;
                    if (self.isChecked() === self.getCode()) {
                        self._initPaywall();
                    }
                };
                script.onerror = function () {
                    window.define = _amdDefine;
                };
                document.head.appendChild(script);
            }

            // Init paywall when this method is selected.
            // Only proceed if SDK is already loaded; if not, script.onload above handles it.
            this.isChecked.subscribe(function (newValue) {
                if (newValue === self.getCode()) {
                    // Brief delay so Knockout finishes rendering the template first.
                    setTimeout(function () {
                        if (window.Comfino && window.Comfino.ComfinoSDK) {
                            self._initPaywall();
                        }
                    }, 50);
                }
            });

            return this;
        },

        _initPaywall: function () {
            if (typeof window.ComfinoPaywallInit !== 'undefined') {
                window.ComfinoPaywallInit.init();
            }
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
        }
    });
});