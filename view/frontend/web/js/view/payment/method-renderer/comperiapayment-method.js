define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/modal/modal'
    ],
    function ($, Component, redirectOnSuccessAction, url, customerData, errorProcessor, fullScreenLoader, modal) {
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            redirectUrl: '',
            defaults: {
                template: 'Comperia_ComperiaGateway/payment/ComperiaGateway',
            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            afterPlaceOrder: function () {
                var gatewayEndpoint = url.build('comperia/application');
                $.post(gatewayEndpoint, 'json')
                    .done(function (response) {
                        window.location.href = response.redirectUrl;
                    })
                    .fail(function (response) {
                        errorProcessor.process(response.error, this.messageContainer);
                    })
                    .always(function () {
                        fullScreenLoader.stopLoader();
                    });
            },

            showCardPayment: function () {
                let gatewayEndpoint = url.build('comperia/application/offers');

                let options = {
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    title: 'Przykład reprezentatywny',
                    buttons: [{
                        text: $.mage.__('Close'),
                        class: 'modal-close',
                        click: function (){
                            this.closeModal();
                        }
                    }]
                };

                $.get(gatewayEndpoint, 'json')
                    .done(function (response) {
                        $.each(response, function( key, value ) {
                            let data = '<div id="offer_' + value.type + '" style="position: relative; max-width: 33.33%; background: #f5f5f5; margin: 0 20px; padding: 22px 30px;">\n' +
                '                <div style="text-align: center">\n' +
                '                    <div class="icon" style="margin-bottom: 10px;">' + value.icon + '</div>\n' +
                '                    <div class="name" style="margin-bottom: 10px;"><strong>' + value.name + '</strong></div>\n' +
                '                    <div class="offer" style="margin-bottom: 10px;">\n' +
                '                        <div><strong > ' + value.loanTerm + ' rat x ' + value.instalmentAmount + ' zł</strong></div>\n' +
                '                        <div>Całkowita kwota do spłaty: ' + value.toPay + ' zł, RRSO: ' + value.rrso + ' %</div>\n' +
                '                    </div>\n' +
                '                    <div class="description" style="margin-bottom: 10px;">' + value.description + '</div>\n' +
                '                    <div><a id="representativeExample_a_' + value.type + '">Przykład reprezentatywny</a></div>\n' +
                '                    <div style="display: none" id="representativeExample_modal_' + value.type + '">\n' +
                '                        <div class="modal-inner-content">\n' +
                '                            <p id="representativeExample_modal_text' + value.type + '"></p>\n' +
                '                        </div>\n' +
                '                    </div>\n' +
                '                </div>\n' +
                '            </div>';

                            $(data).appendTo($('#comfino-offers'));

                            $('#offer_' +  value.type).click(function() {
                                $("div[id^='offer_']").each(function() {
                                    $(this).css({ "border": "none" });
                                });

                                $(this).css({ "border": "1px solid red" });
                            });

                            $('#representativeExample_a_' +  value.type).click(function() {
                                $('#representativeExample_modal_text' +  value.type).html(value.representativeExample);
                                let popup = modal(options, $('#representativeExample_modal_' + value.type));
                                $('#representativeExample_modal_' +  value.type).modal('openModal');
                            });
                        });
                });
            }
        });
    }
);
