define(
    [
        'ko',
        'jquery',
        'Spryng_Payment/js/view/payment/method-renderer/default'
    ],
    function (ko, $, Component) {
        var checkoutConfig = window.checkoutConfig.payment;
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Spryng_Payment/payment/klarna',
                selectedPrefix: null,
                selectedPaymentClass: null
            },
            getCustomerPrefixes: function () {
                return checkoutConfig.prefix;
            },
            getPaymentClasses: function () {
                return checkoutConfig.pclasses;
            },
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        "selected_prefix": this.selectedPrefix,
                        "selected_payment_class": this.selectedPaymentClass,
                        "dob": $('#' + this.item.method + '_dob').val()
                    }
                };
            }
        });
    }
);
