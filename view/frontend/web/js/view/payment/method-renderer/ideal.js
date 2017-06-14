define(
    [
        'ko',
        'jquery',
        'Spryng_Payment/js/view/payment/method-renderer/default',
        'Magento_Ui/js/model/messageList',
        'mage/translate'
    ],
    function (ko, $, Component, messageList, $t) {
        var checkoutConfig = window.checkoutConfig.payment;
        'use strict';
        return Component.extend(
            {
                defaults: {
                    template: 'Spryng_Payment/payment/ideal',
                    selectedIssuer: null
                },
                getIssuers: function () {
                    return checkoutConfig.issuers;
                },
                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            "selected_issuer": this.selectedIssuer
                        }
                    };
                }
            }
        );
    }
);
