define(
    [
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Dialcom_Przelewy/js/view/payment/method-renderer/przelewy-method'
    ],
    function ($t, messageList, PrzelewyComponent) {
        'use strict';
        return {
            validate: function () {
                let przelewy = new PrzelewyComponent();

                if (!przelewy.getTermsAccept()) {
                    return true;
                }

                let isValid = false;

                let $html = jQuery(przelewy.getTermsAccept());
                let $inputs = $html.filter('input');

                if (1 === $inputs.length) {
                    let inputName = $inputs.first().attr('name');
                    let safeName = inputName.replace(/(\[|\])/g, "\\$1");
                    let $realInput = jQuery('input[name=' + safeName + ']');

                    isValid = 'checked' === $realInput.attr('checked');
                }

                if (!isValid) {
                    messageList.addErrorMessage({ message: $t('Please accept Przelewy24 terms.') });
                }

                return isValid;
            }
        }
    }
);
