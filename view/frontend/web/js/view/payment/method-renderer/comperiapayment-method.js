define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader'
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
            }

        });
    }
);
