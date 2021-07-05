<?php

namespace Dialcom\Przelewy\Block\Payment\Przelewy;

class Redirect extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * @var \Magento\Framework\Data\FormFactory
     */
    protected $formFactory;

    /**
     * @var string
     */
    private $orderId;

    /**
     * Redirect constructor.
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    )
    {
        $this->formFactory = $formFactory;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Warning:
     * Magento order related functions names are confusing
     * @see Przelewy::getRedirectionFormData comment for more details
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $calculatedOrderId = !is_null($this->orderId) ? $this->orderId : $objectManager->get('Magento\Checkout\Model\Session')->getLastRealOrderId();

        if ($calculatedOrderId) {
            $order = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($calculatedOrderId);
            $order->addStatusToHistory(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, __('Waiting for payment.'));
            $order->setSendEmail(true);
            $order->save();

            $objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($order);
        }

        $przelewy = $objectManager->create('Dialcom\Przelewy\Model\Payment\Przelewy');

        $form = $this->formFactory->create();
        $form->setAction($przelewy->getPaymentURI(isset($order) ? $order->getOrderCurrencyCode() : $objectManager->get('Magento\Directory\Model\Currency')->getCurrencySymbol()))
            ->setId('przelewy_przelewy_checkout')
            ->setName('przelewy_przelewy_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);

        foreach ($przelewy->getRedirectionFormData($calculatedOrderId) as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>';
        $html .= $form->toHtml();
        $html .= '<script type="text/javascript">document.getElementById("przelewy_przelewy_checkout").submit();</script>';
        $html .= '</body></html>';

        return $html;
    }

    public function getHtml($orderId)
    {
        $this->orderId = $orderId;

        return $this->_toHtml();
    }
}
