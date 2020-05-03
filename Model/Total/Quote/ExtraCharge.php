<?php

namespace Dialcom\Przelewy\Model\Total\Quote;

use Dialcom\Przelewy\Model\Payment\Przelewy;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Class ExtraCharge
 */
class ExtraCharge extends AbstractTotal
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $methodCode = Przelewy::PAYMENT_METHOD_PRZELEWY_CODE;

    /**
     * Payment method
     *
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * Custom constructor.
     */
    public function __construct(\Magento\Payment\Helper\Data $paymentHelper)
    {
        $this->setCode('extra_charge_amount');

        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $cartAmount = number_format($quote->getGrandTotal() * 100, 0, '', '');

        $extraChargeAmount = round($this->method->getExtrachargeAmountByAmount($cartAmount) / 100, 2);

        return [
            'code' => $this->getCode(),
            'title' => $this->getLabel(),
            'value' => $extraChargeAmount,
            'area' => 'hidden',
        ];
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('This payment will be increased by');
    }
}
