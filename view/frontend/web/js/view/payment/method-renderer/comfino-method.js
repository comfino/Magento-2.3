/**
 * Comfino payment method renderer.
 * Sets window.ComfinoPaywallData and injects the Comfino SDK script.
 * All paywall lifecycle logic (init, iframe, offer selection) is handled by the SDK itself
 * via MagentoPaywallController (bootstrapped automatically when window.ComfinoPaywallData is set).
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

            // Expose paywall URL and environment for SDK auto-bootstrap.
            window.ComfinoPaywallData = {
                paywallUrl:  config.paywallUrl  || '',
                environment: config.environment || 'production'
            };

            // Load SDK as a plain script via DOM injection.
            //
            // Why not require([url])?  The SDK is a UMD bundle.  When RequireJS's global
            // define() is present, UMD takes the AMD branch — it calls define() and returns
            // its export to RequireJS, but skips the global assignment (window.Comfino.*).
            //
            // Solution: hide window.define before the script executes so the SDK's UMD wrapper
            // sees no AMD environment, takes the global-assignment branch, and sets
            // window.Comfino.ComfinoSDK.  Restore define() in onload/onerror.
            // By the time the user can navigate to the payment step all Magento/KO modules are
            // already defined, so the brief window where define is hidden is safe.
            //
            // The SDK detects window.ComfinoPaywallData at load time and auto-bootstraps via
            // MagentoPaywallController, which uses waitForContainer to defer paywall creation
            // until #comfino-paywall-container appears in the DOM (after KO renders the template).
            if (config.sdkScriptUrl && !document.querySelector('script[data-comfino-sdk]')) {
                var _amdDefine = window.define;
                window.define = undefined;

                var script = document.createElement('script');
                script.src = config.sdkScriptUrl;
                script.setAttribute('data-comfino-sdk', '1');
                script.onload = function () {
                    window.define = _amdDefine;
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
        }
    });
});