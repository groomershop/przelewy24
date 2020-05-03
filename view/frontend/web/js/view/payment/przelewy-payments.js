/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'dialcom_przelewy',
                component: 'Dialcom_Przelewy/js/view/payment/method-renderer/przelewy-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);