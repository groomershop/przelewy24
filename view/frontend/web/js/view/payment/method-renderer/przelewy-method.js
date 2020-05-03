/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals',
        'Magento_Catalog/js/price-utils',
        'mage/translate',
        'jquery'
    ],
    function (ko, Component, quote, totals, priceUtils, $) {
        'use strict';

        /**
         * Render HTML with knockout
         *
         * @param html
         * @param data
         *
         * @returns {string}
         */
        var renderFromString = function (html, data) {
            var node = new DOMParser().parseFromString(html, "text/html");
            ko.applyBindings(data, node.body);
            var res = node.body.innerHTML.toString();
            ko.cleanNode(node);

            return res;
        };

        /**
         * Get formatted extra charge amount
         *
         * @returns {string|*}
         */
        var getExtraChargeAmount = function () {
            if (totals.getSegment('extra_charge_amount') && totals.getSegment('extra_charge_amount').value > 0) {
                return priceUtils.formatPrice(
                    totals.getSegment('extra_charge_amount').value,
                    quote.getPriceFormat()
                );
            }

            return null;
        };

        return Component.extend({
            defaults: {
                template: 'Dialcom_Przelewy/payment/przelewy-form'
            },

            redirectAfterPlaceOrder: false,

            getCode: function () {
                return 'dialcom_przelewy';
            },

            getBankNames: function () {
                return window.checkoutConfig.payment.dialcom_przelewy.bankNames;
            },

            getDescription: function () {
                return window.checkoutConfig.payment.dialcom_przelewy.description;
            },

            getHiddenInputs: function () {
                return window.checkoutConfig.payment.dialcom_przelewy.hiddenInputs;
            },

            getTermsAccept: function () {
                return window.checkoutConfig.payment.dialcom_przelewy.termsAccept;
            },

            getData: function () {
                var parent = this._super(),
                    additionalData = null;
                additionalData = {};
                additionalData['method_id'] = jQuery('input[name="payment[method_id]"]').val();
                additionalData['method_name'] = jQuery('input[name="payment[method_name]"]').val();
                additionalData['cc_id'] = jQuery('input[name="payment[cc_id]"]').val();
                additionalData['cc_name'] = jQuery('input[name="payment[cc_name]"]').val();
                additionalData['accept_regulations'] = jQuery('input[name="payment[accept_regulations]"]').prop('checked');
                additionalData['p24_forget'] = jQuery('input[name="payment[p24_forget]"]').prop('checked');
                return jQuery.extend(true, parent, {'additional_data': additionalData});
            },

            getOneClickInfo: function () {
                return window.checkoutConfig.payment.dialcom_przelewy.oneClickInfo;
            },

            getExtraChargeInfo: function () {
                return renderFromString(window.checkoutConfig.payment.dialcom_przelewy.extraChargeInfo, {
                    getExtraChargeAmount: getExtraChargeAmount,
                });
            },

            getMethodsList: function () {
                return window.checkoutConfig.payment.dialcom_przelewy.methodsList;
            },

            getLogoUrl: function () {
                return window.checkoutConfig.payment.dialcom_przelewy.logoUrl;
            },

            getCustomScripts: function () {
                return window.checkoutConfig.payment.dialcom_przelewy.customScripts;
            },

            getPaymentMethodAsGateway: function () {
                return renderFromString(window.checkoutConfig.payment.dialcom_przelewy.paymentMethodAsGateway, {
                    getExtraChargeAmount: getExtraChargeAmount,
                });
            },

            afterPlaceOrder: function () {
                window.location.replace(window.checkoutConfig.payment.dialcom_przelewy.redirectUrl);
            },
        });
    }
);