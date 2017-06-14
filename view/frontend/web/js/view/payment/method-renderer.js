define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        Component,
        rendererList
    ) {
        'use strict';
        var defaultComponent = 'Spryng_Payment/js/view/payment/method-renderer/default';
        var creditcardComponent = 'Spryng_Payment/js/view/payment/method-renderer/creditcard';
        var bancontactComponent = 'Spryng_Payment/js/view/payment/method-renderer/bancontact';
        var sepaComponent = 'Spryng_Payment/js/view/payment/method-renderer/sepa';
        var klarnaComponent = 'Spryng_Payment/js/view/payment/method-renderer/klarna';
        var idealComponent = 'Spryng_Payment/js/view/payment/method-renderer/ideal';
        var methods = [
            {type: 'spryng_methods_bancontact', component: bancontactComponent},
            {type: 'spryng_methods_creditcard', component: creditcardComponent},
            {type: 'spryng_methods_ideal', component: idealComponent},
            {type: 'spryng_methods_paypal', component: defaultComponent},
            {type: 'spryng_methods_sepa', component: sepaComponent},
            {type: 'spryng_methods_sofort', component: defaultComponent},
            {type: 'spryng_methods_klarna', component: klarnaComponent}

        ];
        $.each(
            methods,
            function (k, method) {
                rendererList.push(method);
            }
        );
        return Component.extend({});
    }
);