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
    ],
    function (ko, Component, redirectOnSuccessAction, url, customerData, errorProcessor, fullScreenLoader, modal, priceUtils, t) {
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
            offerList: { elements: null, data: null },
            selectedOffer: 0,

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
                    .then(function (data) {
                        fullScreenLoader.stopLoader();
                        window.location.href = data[0].redirectUrl;
                    }).catch(function (error) {
                        fullScreenLoader.stopLoader();
                        errorProcessor.process(error, self.messageContainer);
                    });
            },

            selectTerm(loanTermBox, termElement)
            {
                loanTermBox.querySelectorAll('div > div.comfino-installments-quantity').forEach(function (item) {
                    item.classList.remove('comfino-active');
                });

                if (termElement !== null) {
                    termElement.classList.add('comfino-active');

                    for (let loanParams of this.offerList.data[this.selectedOffer].loanParameters) {
                        if (loanParams.loanTerm === parseInt(termElement.dataset.term)) {
                            document.getElementById('comfino-total-payment').innerHTML = priceUtils.formatPrice(customerData.get('cart')().subtotalAmount);
                            document.getElementById('comfino-monthly-rate').innerHTML = priceUtils.formatPrice(loanParams.instalmentAmount);
                            document.getElementById('comfino-summary-total').innerHTML = priceUtils.formatPrice(loanParams.toPay);
                            document.getElementById('comfino-rrso').innerHTML = this.offerList.data[this.selectedOffer].rrso + '%';
                            document.getElementById('comfino-description-box').innerHTML = this.offerList.data[this.selectedOffer].description;
                            document.getElementById('comfino-repr-example').innerHTML = this.offerList.data[this.selectedOffer].representativeExample;

                            this.offerList.elements[this.selectedOffer].dataset.sumamount = parseFloat(customerData.get('cart')().subtotalAmount);
                            this.offerList.elements[this.selectedOffer].dataset.term = loanParams.loanTerm;

                            this.setLoanType(this.offerList.data[this.selectedOffer].type);
                            this.setLoanTerm(termElement.dataset.term);

                            break;
                        }
                    }
                } else {
                    document.getElementById('comfino-total-payment').innerHTML = priceUtils.formatPrice(customerData.get('cart')().subtotalAmount);
                }
            },

            selectCurrentTerm(loanTermBox, term)
            {
                let termElement = loanTermBox.querySelector('div > div[data-term="' + term + '"]');

                if (termElement !== null) {
                    loanTermBox.querySelectorAll('div > div.comfino-installments-quantity').forEach(function (item) {
                        item.classList.remove('comfino-active');
                    });

                    termElement.classList.add('comfino-active');

                    for (let loanParams of this.offerList.data[this.selectedOffer].loanParameters) {
                        if (loanParams.loanTerm === parseInt(term)) {
                            document.getElementById('comfino-total-payment').innerHTML = priceUtils.formatPrice(customerData.get('cart')().subtotalAmount);
                            document.getElementById('comfino-monthly-rate').innerHTML = priceUtils.formatPrice(loanParams.instalmentAmount);
                            document.getElementById('comfino-summary-total').innerHTML = priceUtils.formatPrice(loanParams.toPay);
                            document.getElementById('comfino-rrso').innerHTML = this.offerList.data[this.selectedOffer].rrso + '%';
                            document.getElementById('comfino-description-box').innerHTML = this.offerList.data[this.selectedOffer].description;
                            document.getElementById('comfino-repr-example').innerHTML = this.offerList.data[this.selectedOffer].representativeExample;

                            this.setLoanType(this.offerList.data[this.selectedOffer].type);
                            this.setLoanTerm(termElement.dataset.term);

                            break;
                        }
                    }
                }
            },

            fetchProductDetails(offerData)
            {
                let self = this;

                if (offerData.type === 'PAY_LATER') {
                    document.getElementById('comfino-payment-delay').style.display = 'block';
                    document.getElementById('comfino-installments').style.display = 'none';
                } else {
                    let loanTermBox = document.getElementById('comfino-quantity-select');
                    let loanTermBoxContents = ``;

                    offerData.loanParameters.forEach(function (item, index) {
                        if (index === 0) {
                            loanTermBoxContents += `<div class="comfino-select-box">`;
                        } else if (index % 3 === 0) {
                            loanTermBoxContents += `</div><div class="comfino-select-box">`;
                        }

                        loanTermBoxContents += `<div data-term="` + item.loanTerm + `" class="comfino-installments-quantity">` + item.loanTerm + `</div>`;

                        if (index === offerData.loanParameters.length - 1) {
                            loanTermBoxContents += `</div>`;
                        }
                    });

                    loanTermBox.innerHTML = loanTermBoxContents;

                    loanTermBox.querySelectorAll('div > div.comfino-installments-quantity').forEach(function (item) {
                        item.addEventListener('click', function (event) {
                            event.preventDefault();
                            self.selectTerm(loanTermBox, event.target);
                        });
                    });

                    document.getElementById('comfino-payment-delay').style.display = 'none';
                    document.getElementById('comfino-installments').style.display = 'block';
                }
            },

            putDataIntoSection(data)
            {
                let self = this;
                let offerElements = [];
                let offerData = [];

                data.forEach(function (item, index) {
                    let comfinoOffer = document.createElement('div');

                    comfinoOffer.dataset.type = item.type;
                    comfinoOffer.dataset.sumamount = customerData.get('cart')().subtotalAmount;
                    comfinoOffer.dataset.term = item.loanTerm;

                    comfinoOffer.classList.add('comfino-order');

                    let comfinoOptId = 'comfino-opt-' + item.type;

                    comfinoOffer.innerHTML = `
                        <div class="comfino-single-payment">
                            <input type="radio" id="` + comfinoOptId + `" class="comfino-input" name="comfino" />
                            <label for="` + comfinoOptId + `">
                                <div class="comfino-icon">` + item.icon + `</div>
                                <span class="comfino-single-payment__text">` + item.name + `</span>
                            </label>
                        </div>
                    `;

                    if (index === 0) {
                        let paymentOption = comfinoOffer.querySelector('#' + comfinoOptId);

                        comfinoOffer.classList.add('selected');
                        paymentOption.setAttribute('checked', 'checked');

                        self.fetchProductDetails(item);
                    }

                    offerData[index] = item;
                    offerElements[index] = document.getElementById('comfino-offer-items').appendChild(comfinoOffer);
                });

                return { elements: offerElements, data: offerData };
            },

            /**
             * Get offers from API.
             */
            initPayments()
            {
                let self = this;
                let offerWrapper = document.getElementById('comfino-offer-items');

                self.isAvailable(true);

                fetch(url.build('rest/V1/comfino-gateway/offers'))
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
                    }).catch(function (error) {
                        offerWrapper.innerHTML = `<div class="message message-error error">` +  t.__('There was an error while performing this operation') + ': ' + error + `</div>`;

                        errorProcessor.process(error, this.messageContainer);
                    });
            }
        });
    }
);
