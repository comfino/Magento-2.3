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

                ComfinoFrontendRenderer.init(options);

                /*
                                self.isAvailable(true);

                                if (self.initialized) {
                                    ComfinoFrontendRenderer.init(window.checkoutConfig.Comfino.frontendRendererOptions);

                                    return;
                                }

                                let script = document.createElement('script');

                                script.onload = () => ComfinoFrontendRenderer.init(window.checkoutConfig.Comfino.frontendRendererOptions);
                                script.src = frontendScriptURL;
                                script.async = true;

                                document.getElementsByTagName('head')[0].appendChild(script);

                                self.initialized = true;
                */
            }

            /*fetch(url.build('rest/V1/comfino-gateway/offers'))
                .then(response => response.json())
                .then(function (data) {
                    if (data === null || data.length === 0) {
                        self.isAvailable(false);

                        return;
                    }

                    let loanTermBox = document.getElementById('comfino-quantity-select');

                    offerWrapper.innerHTML = '';
                    self.offerList = self.putDataIntoSection(data);

                    self.selectTerm(loanTermBox, loanTermBox.querySelector('div > div[data-term="' + self.offerList.data[self.selectedOffer].loanTerm + '"]'));

                    self.offerList.elements.forEach(function (item, index) {
                        item.querySelector('label').addEventListener('click', function () {
                            self.selectedOffer = index;

                            self.fetchProductDetails(self.offerList.data[self.selectedOffer]);

                            self.offerList.elements.forEach(function () {
                                item.classList.remove('selected');
                            });

                            item.classList.add('selected');

                            self.selectCurrentTerm(loanTermBox, self.offerList.elements[self.selectedOffer].dataset.term);
                        });
                    });

                    document.getElementById('comfino-repr-example-link').addEventListener('click', function (event) {
                        event.preventDefault();
                        document.getElementById('modal-repr-example').classList.add('open');
                    });

                    document.getElementById('modal-repr-example').querySelector('button.comfino-modal-exit').addEventListener('click', function (event) {
                        event.preventDefault();
                        document.getElementById('modal-repr-example').classList.remove('open');
                    });

                    document.getElementById('modal-repr-example').querySelector('div.comfino-modal-exit').addEventListener('click', function (event) {
                        event.preventDefault();
                        document.getElementById('modal-repr-example').classList.remove('open');
                    });
                }).catch(function (error) {
                    offerWrapper.innerHTML = `<div class="message message-error error">` +  translator.__('There was an error while performing this operation') + ': ' + error + `</div>`;

                    errorProcessor.process(error, this.messageContainer);
                });
        }*/
        });
    }
);
