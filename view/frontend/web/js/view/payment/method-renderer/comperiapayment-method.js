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
    function ($, Component, redirectOnSuccessAction, url, customerData, errorProcessor, fullScreenLoader) {
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

                $.get(gatewayEndpoint, 'json')
                    .done(function (response) {
                        $.each(response, function( key, value ) {
                            let data = '<div id="offer_' + value.type + '" style="position: relative; max-width: 33.33%; background: #f5f5f5; margin: 0 20px; padding: 22px 30px;">\n' +
                '                <div style="text-align: center">\n' +
                '                    <div class="icon" style="margin-bottom: 10px;">' + value.icon + '</div>\n' +
                '                    <div class="name" style="margin-bottom: 10px;"><strong>' + value.name + '</strong></div>\n' +
                '                    <div class="offer" style="margin-bottom: 10px;">\n' +
                '                        <div><strong > ' + value.loanTerm + ' rat x ' + value.instalmentAmount + ' zł</strong></div>\n' +
                '                        <div>Całkowita kwota do spłaty: ' + value.sumAmount + ' zł, RRSO: ' + value.rrso + ' %</div>\n' +
                '                    </div>\n' +
                '                    <div class="description" style="margin-bottom: 10px;">' + value.description + '</div>\n' +
                '                    <div><a id="example_a_' + value.type + '" href="#">Przykład reprezentatywny</a></div>\n' +
                '                   <div style="desplay: none" id="example_modal_' + value.type + '"></div>\n' +
                '                </div>\n' +
                '            </div>';

                            $(data).appendTo($('#comfino-offers'));

                            $('#offer_' +  value.type).click(function() {
                                $("div[id^='offer_']").each(function() {
                                    $(this).css({ "border": "none" });
                                });

                                $(this).css({ "border": "1px solid red" });
                            });

                            require(
                                [
                                    'jquery',
                                    'Magento_Ui/js/modal/modal'
                                ],
                                function ($, modal) {

                                    var options = {
                                        type: 'popup',
                                        responsive: true,
                                        title: $.mage.__('My Title'),
                                        buttons: []
                                    };

                                    var popup = modal(options, $('.content'));
                                    $("#click-section").on('click',function(){
                                        $('#example_a_' +  value.type).modal("openModal");
                                    });
                                });

                            $('#example_a_' +  value.type).click(function() {
                                require(
                                    [
                                        'jquery',
                                        'jquery/ui',
                                        'Magento_Ui/js/modal/modal'
                                    ],
                                    function(
                                        $,
                                        modal
                                    ) {
                                        let options = {
                                            type: 'popup',
                                            responsive: true,
                                            innerScroll: true,
                                            title: 'Przykład reprezentatywny',
                                            buttons: [{
                                                text: $.mage.__('Continue'),
                                                class: '',
                                                click: function () {
                                                    this.closeModal();
                                                }
                                            }]
                                        };

                                        let popup = modal(options, $('example_modal_' + value.type));
                                        $('#example_modal_' +  value.type).modal('openModal');
                                    }
                                );
                            });
                        });

                    })
                ;
            }
        });
    }
);
