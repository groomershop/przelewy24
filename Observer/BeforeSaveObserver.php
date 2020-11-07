<?php

namespace Dialcom\Przelewy\Observer;

use Dialcom\Przelewy\Model\Payment\Przelewy;
use Dialcom\Przelewy\Przelewy24Class;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class BeforeSaveObserver implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if (Przelewy::PAYMENT_METHOD_PRZELEWY_CODE !== $order->getPayment()->getMethodInstance()->getCode()) {
            return;
        }

        if (Order::STATE_NEW === $order->getState()) {
            $order->setStatus(Przelewy24Class::PENDING_PAYMENT_CUSTOM_STATUS);
        }
    }
}
