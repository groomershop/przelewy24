<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Services\PayloadForRestTransaction;
use Dialcom\Przelewy\Services\RestCard;
use Dialcom\Przelewy\Services\RestTransaction;
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
        $calculatedOrderId = $this->_objectManager->get(Session::class)->getLastRealOrderId();

        $przelewyModel = $this->_objectManager->create(Przelewy::class);

        $order = $this->_objectManager->create(Order::class)->loadByIncrementId($calculatedOrderId);
        $przelewyModel->addExtracharge($order->getEntityId());

        $paymentData = $order->getPayment()->getData();
        $additionalInformation = $paymentData['additional_information'];
        $recurring = $this->_objectManager->create(Recurring::class);
        $storeId = $order->getStoreId();
        $cardRef = $recurring->refIdForCardId($additionalInformation['cc_id']);

        if (!$cardRef) {
            return $this->_redirect('przelewy/przelewy/failure', ['ga_order_id' => $order->getIncrementId()]);
            //return $this->_redirect('przelewy/przelewy/failure', ['ga_order_id' => $order->getEntityId()]);
        }

        $transactionService = new RestTransaction($this->scopeConfig, $storeId);
        $payload = $przelewyModel->getTransactionData($calculatedOrderId, $cardRef);
        $resTransaction = $transactionService->register($payload);
        if (isset($resTransaction['data']['token'])) {
            $token = $resTransaction['data']['token'];
        } else {
            return $this->_redirect('przelewy/przelewy/failure', ['ga_order_id' => $order->getIncrementId()]);
        }
        $cardService = new RestCard($this->scopeConfig, $storeId);
        $resCard = $cardService->chargeWith3ds($token);
        if (!isset($resCard['data']['redirectUrl'])) {
            return $this->_redirect('przelewy/przelewy/failure', ['ga_order_id' => $order->getIncrementId()]);
        }

        return $this->_redirect($resCard['data']['redirectUrl']);
    }
}
