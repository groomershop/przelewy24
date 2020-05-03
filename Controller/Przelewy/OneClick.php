<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

use Dialcom\Przelewy\Helper\Data;
use Magento\Sales\Model\Order;
use Dialcom\Przelewy\Model\Payment\Przelewy;
use Magento\Checkout\Model\Session;
use Dialcom\Przelewy\Model\Recurring;
use Magento\Store\Model\ScopeInterface as Scope;

class OneClick extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Dialcom\Przelewy\Helper\Data
     */
    protected $helper;

    /**
     * OneClick constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Dialcom\Przelewy\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Dialcom\Przelewy\Helper\Data $helper
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = (int) $this->_objectManager->get(Session::class)->getLastRealOrderId();
        $this->_objectManager->create(Przelewy::class)->addExtracharge($orderId);

        $paymentData = $this->_objectManager
            ->create(Order::class)
            ->loadByIncrementId($orderId)
            ->getPayment()
            ->getData()
        ;
        $additionalInformation = $paymentData['additional_information'];
        $recurring = $this->_objectManager->create(Recurring::class);
        $result = $recurring->chargeCard($orderId, (int) $additionalInformation['cc_id']);
        $order = $this->_objectManager->create(Order::class)->load($orderId);
        $storeId = $order->getStoreId();
        if (!$result) {
            return $this->_redirect('przelewy/przelewy/failure', ['ga_order_id' => $orderId]);
        }
        $order = $this->_objectManager->create(Order::class)->loadByIncrementId($orderId);
        $chgState = (int) $this->scopeConfig->getValue(Data::XML_PATH_CHG_STATE, Scope::SCOPE_STORE, $storeId);
        $mkInvoice = (int) $this->scopeConfig->getValue(Data::XML_PATH_MK_INVOICE, Scope::SCOPE_STORE, $storeId);

        if (1 === $chgState) {
            $order->addStatusToHistory(Order::STATE_PROCESSING, __('The payment has been accepted.'), true);
            $order->setState(Order::STATE_PROCESSING, true);
            $order->save();
            if (1 === $mkInvoice) {
                $this->helper->makeInvoiceFromOrder($order);
                $order->setSendEmail(true);
            } else {
                $order->setTotalPaid($order->getGrandTotal());
            }
        }
        $order->save();

        return $this->_redirect('przelewy/przelewy/success', ['ga_order_id' => $orderId]);
    }
}
