define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/modal/modal',
        'Magento_Catalog/js/price-utils',
        'mage/translate',
        window.checkoutConfig.Comfino.frontendScriptURL,
    ],
    function (ko, Component, redirectOnSuccessAction, url, customerData, errorProcessor, fullScreenLoader, modal, priceUtils, translator, ComfinoFrontendRenderer) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            redirectUrl: '',
            defaults: {
                template: 'Comfino_ComfinoGateway/payment/ComfinoGateway',
                loanType: null,
                loanTerm: null,
                isAvailable: ko.observable(false)
            },
            options: null,
            initialized: false,

            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'type': this.loanType
                    }
                };
            },

            setLoanType: function (type) {
                this.loanType = type;
            },

            setLoanTerm: function (term) {
                this.loanTerm = term;
            },

            isMethodAvailable: function () {
                return this.isAvailable();
            },

            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            afterPlaceOrder: function () {
                let self = this;

                fetch(url.build('rest/V1/comfino-gateway/application/save'), { method: 'POST' })
                    .then(response => response.json())
                    .then((data) => {
                        fullScreenLoader.stopLoader();
                        window.location.href = data[0].redirectUrl;
                    }).catch((error) => {
                        fullScreenLoader.stopLoader();
                        errorProcessor.process(error, self.messageContainer);
                    });
            },

            initPayments()
            {
                let self = this;
                let options = window.checkoutConfig.Comfino.frontendRendererOptions;
                options.frontendInitElement = document.getElementById('comfino');
                options.frontendTargetElement = document.getElementById('comfino-offers');
                options.onOfferLoadSuccess = (data) => { self.isAvailable(true); }
                options.onOfferLoadError = (offerWrapper, error) => {
                    self.isAvailable(false);

                    return true;
                };

                window.ComfinoFrontendRenderer = ComfinoFrontendRenderer;

                ComfinoFrontendRenderer.init(options);
            }
        });
    }
);
