define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Dialcom_Przelewy/js/model/accept-regulations-validator'
    ],
    function (Component, additionalValidators, acceptRegulationsValidator) {
        'use strict';
        additionalValidators.registerValidator(acceptRegulationsValidator);
        return Component.extend({});
    }
);
