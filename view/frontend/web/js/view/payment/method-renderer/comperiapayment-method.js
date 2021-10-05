    define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/modal/modal',
        'mage/translate',
    ],
    function ($, ko, Component, redirectOnSuccessAction, url, customerData, errorProcessor, fullScreenLoader, modal, t) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            redirectUrl: '',
            defaults: {
                template: 'Comperia_ComperiaGateway/payment/ComperiaGateway',
                loanType: null,
                loanTerm: null,
                isAvailable: ko.observable(false)
            },
            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'type': this.loanType,
                        'term': this.loanTerm
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
                var gatewayEndpoint = url.build('rest/V1/comperia-gateway/application/save');
                $.post(gatewayEndpoint)
                    .done(function (response) {
                        window.location.href = JSON.parse(response).redirectUrl;
                    })
                    .fail(function (response) {
                        errorProcessor.process(response.error, this.messageContainer);
                    })
                    .always(function () {
                        fullScreenLoader.stopLoader();
                    });
            },

            showCardPayment: function () {
                let gatewayEndpoint = url.build('rest/V1/comperia-gateway/offers');

                let options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: $.mage.__('Representative Example'),
                    buttons: [{
                        text: $.mage.__('Close'),
                        class: 'modal-close',
                        click: function () {
                            this.closeModal();
                        }
                    }]
                };

                $.ajax({
                    type: 'GET',
                    url: gatewayEndpoint,
                    context: this,
                    data: 'json',
                }).done(function (response) {
                    let self = this;
                    if (response.length) {
                        self.isAvailable(true);
                        $.each(response, function (key, value) {
                            self.addOffer(value, options);
                        });
                        self.selectFirstOffer();
                    }
                });
            },
            addOffer: function (value, options) {
                let self = this;
                this.appendData(value);
                this.addOnClickEvents(self, value);
                this.addModal(value, options);
            },
            appendData: function (value) {
                let data = '<div class="offer-box" id="offer_' + value.type + '" style="position: relative; max-width: 33.33%; background: #f5f5f5; margin: 0 20px; padding: 22px 30px;">\n' +
                    '                <div style="text-align: center">\n' +
                    '                    <div class="icon" style="margin-bottom: 10px;">' + value.icon + '</div>\n' +
                    '                    <div class="name" style="margin-bottom: 10px;"><strong>' + value.name + '</strong></div>\n' +
                    '                    <div class="offer" style="margin-bottom: 10px;">\n' +
                    '                        <div><strong > ' + value.loanTerm + '  ' + $.mage.__('rates') + ' x ' + value.instalmentAmount + '</strong></div>\n' +
                    '                        <div>' + $.mage.__('Total amount to pay') + ': ' + value.toPay + ', RRSO: ' + value.rrso + ' %</div>\n' +
                    '                    </div>\n' +
                    '                    <div class="description" style="margin-bottom: 10px;">' + value.description + '</div>\n' +
                    '                    <div><a id="representativeExample_a_' + value.type + '">' + $.mage.__('Representative Example') + '</a></div>\n' +
                    '                    <div style="display: none" id="representativeExample_modal_' + value.type + '">\n' +
                    '                        <div class="modal-inner-content">\n' +
                    '                            <p id="representativeExample_modal_text' + value.type + '"></p>\n' +
                    '                        </div>\n' +
                    '                    </div>\n' +
                    '                </div>\n' +
                    '            </div>';

                $(data).appendTo($('#comfino-offers'));
            },
            addOnClickEvents: function (self, value) {
                $('#offer_' + value.type).click(function () {
                    $(".offer-box").css({"border": "none"});
                    $(this).css({"border": "1px solid red"});
                    self.setLoanType(value.type);
                    self.setLoanTerm(value.loanTerm);
                });
            },
            addModal: function (value, options) {
                $('#representativeExample_a_' + value.type).click(function () {
                    $('#representativeExample_modal_text' + value.type).html(value.representativeExample);
                    let representativeModal = $('#representativeExample_modal_' + value.type);
                    modal(options, representativeModal);
                    representativeModal.modal('openModal');
                });
            },
            selectFirstOffer: function () {
                $(".offer-box").first().click();
            }
        });
    }
);
