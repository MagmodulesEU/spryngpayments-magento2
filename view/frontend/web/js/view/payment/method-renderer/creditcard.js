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
                    template: 'Spryng_Payment/payment/creditcard',
                    selectedIssuer: null
                },
                preparePayment: function () {

                    var self = this;
                    var $form = $('#' + this.getCode() + '-form');

                    if ($form.validation() && $form.validation('isValid')) {
                        this.messageContainer.clear();

                        var methodCode = this.getCode();
                        var apiEndpoint = checkoutConfig.api_endpoint[this.item.method];
                        var spryngOrganisation = checkoutConfig.organisation[this.item.method];
                        var spryngAccount = checkoutConfig.account[this.item.method];

                        $('#' + methodCode + '_card_number').attr('disabled', 'disabled');
                        $('#' + methodCode + '_card_expiry_month').attr('disabled', 'disabled');
                        $('#' + methodCode + '_card_expiry_year').attr('disabled', 'disabled');
                        $('#' + methodCode + '_card_cvc').attr('disabled', 'disabled');

                        var callSuccess = function (res) {
                            $('#' + methodCode + '_card_token').val(res._id);
                            $('#' + methodCode + '_card_number').val('');
                            $('#' + methodCode + '_card_expiry_month').val('');
                            $('#' + methodCode + '_card_expiry_year').val('');
                            $('#' + methodCode + '_card_cvc').val('');

                            if (res._id) {
                                self.placeOrder();
                            }
                        };

                        var callError = function (res) {
                            $('#' + methodCode + '_card_number').attr('disabled', false);
                            $('#' + methodCode + '_card_expiry_month').attr('disabled', false);
                            $('#' + methodCode + '_card_expiry_year').attr('disabled', false);
                            $('#' + methodCode + '_card_cvc').attr('disabled', false);

                            var msg = $t('Error, please try again');
                            alert(msg);
                        };

                        var data =
                        {
                            card_number: $('#' + methodCode + '_card_number').val(),
                            expiry_month: $('#' + methodCode + '_card_expiry_month').val(),
                            expiry_year: $('#' + methodCode + '_card_expiry_year').val(),
                            cvv: $('#' + methodCode + '_card_cvc').val(),
                            organisation: spryngOrganisation,
                            account: spryngAccount
                        };

                        $.ajax(
                            {
                                url: apiEndpoint,
                                method: "POST",
                                data: data,
                                success: callSuccess,
                                error: callError
                            }
                        );
                    } else {
                        return $form.validation() && $form.validation('isValid');
                    }
                },

                getCcMonths: function () {
                    return checkoutConfig.months[this.item.method];
                },

                getCcYears: function () {
                    return checkoutConfig.years[this.item.method];
                },

                getData: function () {
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            "card_token": $('#' + this.item.method + '_card_token').val()
                        }
                    };
                }
            }
        );
    }
);
