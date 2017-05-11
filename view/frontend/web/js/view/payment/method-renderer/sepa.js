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
                template: 'Spryng_Payment/payment/sepa',
                selectedPrefix: null,
            },
            getCustomerPrefixes: function () {
                return checkoutConfig.prefix;
            },
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        "selected_prefix": this.selectedPrefix
                    }
                };
            }
        });
    }
);
