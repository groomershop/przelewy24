<?php

namespace Dialcom\Przelewy\Observer;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Przelewy24Class;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class BeforeSaveObserver implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (null === $order->getPayment()) {
            return;
        }

        $isSapCompatibility = $this->scopeConfig->getValue(
            Data::XML_PATH_SAP_COMPATIBILITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($this->shouldStatusChange($order, $isSapCompatibility)) {
            $order->setStatus(Przelewy24Class::PENDING_PAYMENT_CUSTOM_STATUS);
            $this->logger->info(
                sprintf(
                    'Observer setting status %s, SAP compatibility is %s',
                    Przelewy24Class::PENDING_PAYMENT_CUSTOM_STATUS,
                    ($isSapCompatibility) ? 'ON' : 'OFF'
                )
            );
        }
    }

    /**
     * note: Order of checks is important.
     *
     * @param Order $order
     * @param bool  $isSapCompatibility
     *
     * @return bool
     */
    private function shouldStatusChange($order, $isSapCompatibility)
    {
        if (!(Order::STATE_NEW === $order->getState())) {
            return false;
        }

        if (!$isSapCompatibility) {

            return true;
        }

        if (!('canceled' === $order->getStatus())) {

            return true;
        }

        return false;
    }
}
