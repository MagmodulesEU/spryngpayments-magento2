define(
    [
        'ko',
        'jquery',
        'Spryng_Payment/js/view/payment/method-renderer/default',
        'Magento_Checkout/js/model/quote'
    ],
    function (ko, $, Component, quote) {
        var checkoutConfig = window.checkoutConfig.payment;
        'use strict';
        return Component.extend(
            {
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
                getDob: function () {
                    var dob = window.checkoutConfig.quoteData.customer_dob;
                    if (dob == null) {
                        return ko.observable(false);
                    }
                    return ko.observable(new Date(dob));
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
            }
        );
    }
);
