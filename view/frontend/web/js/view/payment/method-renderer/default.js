define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function ($, Component, url) {
        var checkoutConfig = window.checkoutConfig.payment;
        'use strict';
        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Spryng_Payment/payment/default'
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('spryng/checkout/process/'));
            },
            getInstructions: function () {
                return checkoutConfig.instructions[this.item.method];
            }
        });
    }
);
