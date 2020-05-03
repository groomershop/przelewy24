<?php

namespace Dialcom\Przelewy\Model\Payment;

use Dialcom\Przelewy\Model\Config\Channels;
use Dialcom\Przelewy\Model\Config\Installment;
use Dialcom\Przelewy\Model\Recurring;

class PrzelewyConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var string
     */
    protected $methodCode = Przelewy::PAYMENT_METHOD_PRZELEWY_CODE;

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    private $totalCart;
    private $shippingAmount;
    private $paymentList;
    private $originalPaymentList;
    private $channelsInstallment;
    private $channelsCards;
    private $payMethodFirst;
    private $payMethodSecond;
    private $lastPaymentMethod;
    private $cards;
    private $payMethodPromoted;

    /**
     * PrzelewyConfigProvider constructor.
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->storeManager = $storeManager;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->totalCart = $this->objectManager->get('Magento\Checkout\Model\Session')->getQuote()->getGrandTotal();
        $this->shippingAmount= $this->objectManager->get('Magento\Checkout\Model\Session')->getQuote()->getShippingAmount();

        $this->paymentList = $this->method->getBlock()->getPaymentChannels();
        $this->channelsInstallment = Channels::getChannelsInstallment();
        $this->channelsCards = Recurring::getChannelsCards();
        $this->payMethodFirst = $this->removeMethod142and145(
            $this->paymentList,
            explode(',', $this->method->getPayMethodFirst())
        );
        $this->payMethodSecond = $this->removeMethod142and145(
            $this->paymentList,
            explode(',', $this->method->getPayMethodSecond())
        );

        $this->lastPaymentMethod = $this->method->getBlock()->getLastPaymentMethod();
        $this->cards = $this->method->getOneClick() ? $this->method->getBlock()->getCards() : array();
        $this->payMethodPromoted = explode(',', $this->method->getPayMethodPromoted());
    }

    /**
     * Remove method 142 and 145 if 218 exists.
     *
     * @param array $methodArray
     * @return array
     */
    private static function removeMethod142and145(array $paymentList, array $methodArray) {
        if (array_key_exists(218, $paymentList)) {
            return array_values(array_diff($methodArray, array(142, 145)));
        }

        return $methodArray;
    }


    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'dialcom_przelewy' => [
                    'bankNames' => $this->getBankNames(),
                    'description' => $this->getDescription(),
                    'hiddenInputs' => $this->getHiddenInputs(),
                    'termsAccept' => $this->getTermsAccept(),
                    'redirectUrl' => $this->getRedirectUrl(),
                    'oneClickInfo' => $this->getOneClickInfo(),
                    'extraChargeInfo' => $this->getExtraChargeInfo(),
                    'methodsList' => $this->getMethodsList(),
                    'logoUrl' => $this->getLogoUrl(),
                    'customScripts' => $this->getCustomScripts(),
                    'paymentMethodAsGateway' => $this->getPaymentMethodAsGateway()
                ],
            ],
        ] : [];
    }

    protected function getDescription()
    {
        return $this->method->getBlock()->getDescription();
    }

    protected function getBankNames()
    {
        //wyrzuca z listy metod płatności raty, jeśli kwota zamówienia poniżej progu
        if ($this->totalCart < Przelewy::getMinRatyAmount()) {
            foreach ($this->paymentList as $bankId => $bankName) {
                if (in_array($bankId, $this->channelsInstallment)) {
                    unset($this->paymentList[$bankId]);
                }
            }
        }

        $this->originalPaymentList = $this->paymentList;
        foreach ($this->paymentList as $id => $item) {
            if (!in_array($id, $this->payMethodFirst) && !in_array($id, $this->payMethodSecond)) {
                $this->payMethodSecond[] = $id;
            }
        }

        $resultList = json_encode($this->paymentList);

        return $resultList;
    }

    protected function getHiddenInputs()
    {
        $inputs = <<<HTML
    <input type="hidden" value="" data-bind="value: value, valueUpdate: 'change', attr: { name: inputName }" name="payment[method_id]">
    <input type="hidden" value="" data-bind="value: value, valueUpdate: 'change', attr: { name: inputName }" name="payment[method_name]">
    <input type="hidden" value="" data-bind="value: value, valueUpdate: 'change', attr: { name: inputName }" name="payment[cc_id]">
    <input type="hidden" value="" data-bind="value: value, valueUpdate: 'change', attr: { name: inputName }" name="payment[cc_name]">
HTML;

        return $inputs;
    }

    protected function getTermsAccept($postfix = '')
    {
        $result = '';
        if ($this->method->getRegulationAccept() == '1') {
            $acceptText = __('Yes, I have read and accept');
            $termsText = __('Przelewy24.pl terms');

            $result = <<<HTML
                    <input type="checkbox" name="payment[accept_regulations]" id="p24_accept_regulations{$postfix}" value="1" class="checkbox" style="display: inline-block;">
                    <label for="p24_accept_regulations{$postfix}">{$acceptText}
                        <a style="float:none;margin:0" href="http://www.przelewy24.pl/regulamin.htm">{$termsText}</a>.</label>
HTML;
        }

        return $result;
    }

    protected function getRedirectUrl()
    {
        return $this->method->getOrderPlaceRedirectUrl();
    }

    protected function getOneClickInfo($postfix = '')
    {
        $result = '';
        if ($this->method->getOneClick() == '1') {
            $info = __('In case of paying by card, the card\'s reference number will be saved for further payments.');

            $result = <<<HTML
                <p style="font-size:small;font-style: italic;">{$info}</p>
HTML;
            $p24Forget = \Dialcom\Przelewy\Model\Recurring::getP24Forget();

            if ( $this->objectManager->get('Magento\Customer\Model\Session')->isLoggedIn()) {
                $msg = __('Do not remember my cards');

                // $p24Forget == 1 is not save credit card
                $checked = $p24Forget == 1 ? 'checked="checked"' : '';

                $result .= <<<HTML
                    <p style="font-size:small;font-style: italic; margin-bottom: 0.3em">
                    <input type="checkbox" name="payment[p24_forget]" id="p24_forget{$postfix}" value="1" title="{$msg}" class="checkbox" {$checked}>
                    <label for="p24_forget{$postfix}">{$msg}</label>
                    </p>
HTML;
            }
        }

        return $result;
    }

    protected function getExtraChargeInfo()
    {
        $result = '';
        $cartAmount = number_format(($this->totalCart * 100) + ($this->objectManager->get('Magento\Checkout\Model\Session')->getQuote()->getShippingAmount() * 100), 0, "", "");
        $extraChargeAmount = $this->method->getExtrachargeAmountByAmount($cartAmount);
        if ($extraChargeAmount > 0) {
            $msg = __('This payment will be increased by');

            $result = <<<HTML
                <p style="font-size:small;font-style: italic;">{$msg} <b data-bind="html: getExtraChargeAmount()"></b>.</p>
HTML;
        }

        return $result;
    }

    protected function getMethodsList()
    {
        $result = '';
        if ($this->method->getShowPayMethods()) {
            if ($this->method->getUseGraphical()) {
                $result = $this->getGraphicalMethodsList();
            } else {
                $result = $this->getTextMethodsList();
            }
        }

        return $result;
    }

    private function getGraphicalMethodsList()
    {
        $makeUnfold = false;
        $bankHtml = '';
        $bankHtmlMore = '';
        $unfold = '';
        $payment_list = $this->originalPaymentList;

        if ($this->lastPaymentMethod) {
            $bankHtml .= $this->method->getBlock()->getBankHtml($this->lastPaymentMethod, __('Recently used'));
            $makeUnfold = true;
        }

        foreach ($this->cards as $card) {
            $bankHtml .= $this->method->getBlock()->getBankHtml(
                md5($card['card_type']), __('Your card'),
                substr($card['mask'], -9), $card['id'], 'recurring'
            );
            $makeUnfold = true;
        }

        if (sizeof($this->payMethodFirst) == 0 || (sizeof($this->payMethodFirst) == 1 && empty($this->payMethodFirst[0]))) {
            $this->payMethodFirst = $this->payMethodSecond;
            $this->payMethodSecond = array();
            $makeUnfold = false;
        }

        foreach ($this->payMethodFirst as $bank_id) {
            if (isset($payment_list[$bank_id]) && $bank_id != $this->lastPaymentMethod) {
                $bankHtml .= $this->method->getBlock()->getBankHtml($bank_id, $payment_list[$bank_id]);
            }
        }


        foreach ($this->payMethodSecond as $bank_id) {
            if (isset($payment_list[$bank_id]) && !in_array($bank_id, $this->payMethodFirst) && $bank_id != $this->lastPaymentMethod) {
                $bankHtmlMore .= $this->method->getBlock()->getBankHtml($bank_id, $payment_list[$bank_id]);
                $makeUnfold = true;
            }
        }

        foreach ($payment_list as $bank_id => $bank_name) {
            if (!in_array($bank_id, $this->payMethodFirst) && !in_array($bank_id, $this->payMethodSecond) && $bank_id != $this->lastPaymentMethod) {
                $bankHtmlMore .= $this->method->getBlock()->getBankHtml($bank_id, $bank_name);
                $makeUnfold = true;
            }
        }

        if ($makeUnfold) {
            $unfoldMessage = __('More payment methods');
            $displayNone = 'style="display: none"';
            $unfold = <<<HTML
                 <div class="moreStuff"
                 onclick="
                    require(['jquery'], function ($) {
                            $('.moreStuff').fadeOut(100);
                            $('.morePayMethods').slideDown();
                    });
                 "
                  title="{$unfoldMessage}"></div>
HTML;
        } else {
            $displayNone = '';
        }

        $result = <<<HTML
            <div class="payMethodList">
                {$bankHtml}
                <div style="clear:both"></div>
                <div class="morePayMethods" {$displayNone}>
                    {$bankHtmlMore}
                    <div style="clear:both"></div>
                </div>
                {$unfold}
            </div>
HTML;

        return $result;
    }

    private function getTextMethodsList()
    {
        $first = 1;
        $makeUnfold = false;
        $bankText = '';
        $bankTextMore = '';
        $unfold = '';
        $payment_list = $this->originalPaymentList;

        if ($this->lastPaymentMethod) {
            $bankText .= $this->method->getBlock()->getBankTxt($this->lastPaymentMethod, __('Last used:') . $payment_list[$this->lastPaymentMethod], $first-- > 0);
            $makeUnfold = true;
        }

        if (!empty($this->cards)) {
            foreach ($this->cards as $card) {
                $bankText .= $this->method->getBlock()->getBankTxtUnescaped("",
                    __('Use card') . ' ' . __('Your card') . ' <b>' . $card['mask'] . '</b> <a style="font-size:smaller;margin:0 1em" href="' .
                    filter_var($this->method->getBlock()->getMyCardsUrl($card['id']), FILTER_SANITIZE_URL) . '" onclick="return confirm(\'' . __('Are You sure?') . '\');">'.__('Delete').'</a>',
                    $first-- > 0, $card['id'], $card['card_type'] . ' - ' . $card['mask']
                );
                $makeUnfold = true;
            }
        }

        foreach ($this->payMethodFirst as $bank_id) {
            if (isset($payment_list[$bank_id]) && $bank_id != $this->lastPaymentMethod) {
                $bankText .= $this->method->getBlock()->getBankTxt($bank_id, $payment_list[$bank_id], $first-- > 0);
                $makeUnfold = true;
            }
        }

        foreach ($this->payMethodSecond as $bank_id) {
            if (isset($payment_list[$bank_id]) && !in_array($bank_id, $this->payMethodFirst) && $bank_id != $this->lastPaymentMethod) {
                $bankTextMore .= $this->method->getBlock()->getBankTxt($bank_id, $payment_list[$bank_id], $first-- > 0);
            }
        }

        foreach ($payment_list as $bank_id => $bank_name) {
            if (!in_array($bank_id, $this->payMethodFirst) && !in_array($bank_id, $this->payMethodSecond) && $bank_id != $this->lastPaymentMethod) {
                $bankTextMore .= $this->method->getBlock()->getBankTxt($bank_id, $bank_name, $first-- > 0);
            }
        }

        if ($makeUnfold) {
            $unfoldMessage = __('More payment methods');
            $displayNone = 'style="display: none"';
            $unfold = <<<HTML
                 <div class="moreStuff"
                 onclick="
                    require(['jquery'], function ($) {
                            $('.moreStuff').fadeOut(100);
                            $('.morePayMethods').slideDown();
                    });
                 "
                  title="{$unfoldMessage}"></div>
HTML;
        } else {
            $displayNone = '';
        }

        $result = <<<HTML
            <ul class="form-list">
                {$bankText}
                <div style="clear:both"></div>
                    <div class="morePayMethods txtStyle" {$displayNone}>
                    {$bankTextMore}
                    </div>
                <div style="clear:both"></div>
                {$unfold}
            </ul>
HTML;

        return $result;
    }

    protected function getLogoUrl()
    {
        return $this->method->getBlock()->getLogoUrl();
    }

    protected function getCustomScripts()
    {
        $scriptUrl = $this->method->getBlock()->p24getJsUrl();
        return <<<HTML
            <script type="text/javascript">
                require(['jquery'], function ($) {
                    $.getScript('{$scriptUrl}');
                });
            </script>
HTML;
    }

    protected function getPaymentMethodAsGateway()
    {
        $display_promoted = array();

// raty na liście bramek
        if ((int)$this->method->getInstallment() > Installment::SHOW_NOT && $this->totalCart >= Przelewy::getMinRatyAmount() && is_array($this->channelsInstallment)) {
            foreach ($this->channelsInstallment as $channelInstallment) {
                if (isset($this->paymentList[$channelInstallment]) && !in_array($channelInstallment, $display_promoted)) {
                    $display_promoted[] = $channelInstallment;
                }
            }
        }

// formy płatności na liście bramek
        $promoted_items = $this->payMethodPromoted;
        if ($this->method->getShowPromoted() == '1' && sizeof($promoted_items) > 0 && !(sizeof($promoted_items) == 1 && empty($promoted_items[0]))) {
            foreach ($promoted_items as $p24channelId) {
                if (isset($this->paymentList[$p24channelId]) && !in_array($p24channelId, $this->channelsInstallment) && !in_array($p24channelId, $display_promoted)) {

                    // jeśli karta, to dopisz do listy zapisane karty
                    if (in_array((int)$p24channelId, $this->channelsCards)) {
                        foreach ($this->cards as $card) {
                            $display_promoted[] = $p24channelId . '|' . $card['id'];
                            $this->paymentList[$p24channelId . '|' . $card['id']] = $card['card_type'] . ' ' . substr($card['mask'], -9);
                            $this->paymentList[$p24channelId . '|' . $card['id']] = __('Your card') . ' ' . substr($card['mask'], -9);
                        }
                    }

                    // dopisz to listy tę formę
                    $display_promoted[] = (int)$p24channelId;
                }
            }
        }

        $result = $this->getPromotedHtml($display_promoted);

        return $result;
    }

    private function getPromotedHtml($display_promoted)
    {
        $result = '';
        $addJS = false;
        // wyświetl formy płatności na liście bramek, jeśli są
        foreach ($display_promoted as $p24channelId) {
            $exploded = explode('|', $p24channelId);
            $recurring = count($exploded) === 2;
            $code = 'dialcom_przelewy_' . $p24channelId;
            $label = $this->paymentList[$p24channelId];
            $imgUrl = filter_var($recurring ? $this->method->getBlock()->getCardImgUrl() : 'https://secure.przelewy24.pl/template/201312/bank/logo_' . $p24channelId . '.gif', FILTER_SANITIZE_URL);
            $placeOrderText = __('Place Order');
            $postfix = $recurring ? '_' . str_replace('|', '_', $p24channelId) : '_' . $p24channelId;
            $oneClickInfo = $recurring || in_array($p24channelId, Recurring::getChannelsCards()) ? $this->getOneClickInfo($postfix) : '';

            $result .= <<<HTML
                <div class="payment-method">
                    <div class="payment-method-title field choice">
                        <input type="radio"
                               name="payment[method]"
                               class="radio"
                               autocomplete="off"
                               data-value="dialcom_przelewy_method_{$p24channelId}"
                               data-fake="dialcom_przelewy"
                               data-method="{$p24channelId}"
                               id="{$code}"
                               value="{$code}" />
                        <label for="{$code}" class="label">
                            <img src="{$imgUrl}" class="payment-icon"/>
                            <span>{$label}</span>
                        </label>
                    </div>
                    <div class="payment-method-content">
                        <p style="padding-left:15px;font-size:small;font-style: italic;"></p>
                        <span>{$oneClickInfo}</span>
                        <span>{$this->getExtraChargeInfo()}</span>
                        <p style="font-size:small;font-style: italic; margin-bottom: 0.3em">{$this->getTermsAccept($postfix)}</p>
                        <div class="actions-toolbar">
                            <div class="primary">
                                <button class="action primary checkout dialcomPrzelewyFakeMethodSubmit"
                                            type="submit"
                                            data-postfix="{$postfix}"
                                            title="{$placeOrderText}">
                                    <span>{$placeOrderText}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
HTML;
            $addJS = true;
        }

        if ($addJS) {
            $result .= $this->getPromotedJS();
        }

        return $result;
    }

    private function getPromotedJS()
    {
        return <<<HTML
            <script type="text/javascript">
                require(['jquery', 'Magento_Checkout/js/action/select-payment-method'], function ($, selectPaymentMethod) {
                    $('.payment-method').click(function () {
                        $('.payment-method').removeClass('_active');
                        $(this).addClass('_active');

                        var element = $(this).children().children();
                        var attr = element.attr('data-fake');
                        if (typeof attr !== typeof undefined && attr !== false && attr === 'dialcom_przelewy') {
                            selectPaymentMethod({method: 'dialcom_przelewy'});
                            $('[data-value*=dialcom_przelewy]').each(function () {
                                $(this).val($(this).attr('data-value'));
                            });

                            var newValue = $(element).attr('data-fake');
                            var obChange = $('[name="payment[method]"][value="' + newValue + '"]');
                            obChange.val(obChange.attr('data-value'));
                            $(element).val(newValue);
                            if (parseInt($(element).attr('data-method')) > 0) {
                                var chosenMethod = $(element).attr('data-method').split('|');
                                setP24FakeMethod(chosenMethod[0]);
                                setP24FakeRecurringId("");
                                if (chosenMethod.length == 2) {
                                    setP24FakeRecurringId(chosenMethod[1], $(element).next('label').text());
                                }
                            }

                            function setP24FakeMethod(method) {
                                method = parseInt(method);
                                $('input[name="payment[method_id]"]').val(method > 0 ? method : "");
                                $('input[name="payment[method_name]"]').val(method > 0 ? getFakeBankName(method) : "");
                            }

                            function setP24FakeRecurringId(id, name) {
                                id = parseInt(id);
                                if (name == undefined) name = $('[data-cc=' + id + '] .bank-name').text().trim() + ' - ' + $('[data-cc=' + id + '] .bank-logo span').text().trim();
                                $('input[name="payment[cc_id]"]').val(id > 0 ? id : "");
                                $('input[name="payment[cc_name]"]').val(id > 0 ? name : "");
                                if (id > 0) setP24FakeMethod(0);
                            }

                            function getFakeBankName(id) {
                               return JSON.parse($('#p24bankNames').val())[parseInt(id)];
                            }
                        }
                    });
                });
            </script>
HTML;
    }

}
