<?php

namespace Dialcom\Przelewy\Observer;

use Dialcom\Przelewy\Model\Payment\Przelewy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Class BeforeSaveOrderObserver
 */
class BeforeSaveOrderObserver implements ObserverInterface
{
    /** @var Przelewy */
    protected $przelewy;

    /**
     * BeforeSaveOrderObserver constructor.
     *
     * @param Przelewy $przelewy
     */
    public function __construct(Przelewy $przelewy)
    {
        $this->przelewy = $przelewy;
    }

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

        $this->przelewy->addExtraChargeToOrder($order);
    }
}
