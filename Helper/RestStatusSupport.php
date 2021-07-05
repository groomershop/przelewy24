<?php
namespace Dialcom\Przelewy\Helper;

use Dialcom\Przelewy\Model\Config\Waluty;
use Dialcom\Przelewy\Services\PayloadForRestTransactionVerify;
use Dialcom\Przelewy\Services\RestTransaction;
use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\ScopeInterface;

/**
 * Class RestStatusSupport
 */
class RestStatusSupport extends AbstractHelper
{
    /**
     * Object manager.
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * RestStatusSupport constructor.
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(Context $context, ObjectManagerInterface $objectManager)
    {
        parent::__construct($context);
        $this->objectManager = $objectManager;
    }

    /**
     * Check status.
     *
     * @return bool
     */
    public function checkStatus()
    {
        $statusPayload = $this->getJsonFromBody();
        if (!$statusPayload) {
            return false;
        } elseif (!isset($statusPayload['sessionId'])) {
            return false;
        }

        list($orderId, $saSid) = explode('|', $statusPayload['sessionId'], 2);
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            return false;
        }
        $order = $this->objectManager->create('Magento\Sales\Model\Order')->load($orderId);
        $payment = $order->getPayment();
        $storeId = $order->getStoreId();
        if ($payment) {
            $payment->setData('transaction_id', (int)$statusPayload['orderId']);
            $payment->addTransaction(Transaction::TYPE_ORDER);
        }
        $fullConfig = Waluty::getFullConfig($order->getOrderCurrencyCode(), $this->scopeConfig, $storeId);

        $verified = false;
        if ($this->verify($order, $statusPayload, $fullConfig)) {
            if ($this->externalVerify($order, $statusPayload, $fullConfig)) {
                $verified = true;
            }
        }

        if ($verified) {
            $sendOrderUpdateEmail = $this->onSuccess($order);
        } else {
            $sendOrderUpdateEmail = $this->onFailure($order);
        }

        if ($sendOrderUpdateEmail == true) {
            $order->setSendEmail(true);
        }
        $orderRepo = $this->objectManager->get(OrderRepositoryInterface::class);
        $orderRepo->save($order);

        return $verified;
    }

    /**
     * Sign.
     *
     * @param array $payload
     * @param string $salt
     * @return string
     */
    private function sign($payload, $salt)
    {
        unset($payload['sign']);
        $payload['crc'] = $salt;
        $string = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sign = hash('sha384', $string);

        return $sign;
    }

    /**
     * Get JSON from body.
     *
     * @return array
     */
    private function getJsonFromBody()
    {
        $body = file_get_contents('php://input');
        $json = json_decode($body, true);
        if (!is_array($json)) {
            $json = (array) $json;
        }

        return $json;
    }

    /**
     * Verify payload.
     *
     * @param Order $order
     * @param array $payload
     * @param array $config
     * @return bool
     */
    private function verify($order, $payload, $config)
    {
        $totalAmount = number_format($order->getGrandTotal() * 100, 0, "", "");

        if ($payload['merchantId'] !== (int)$config['merchant_id']) {
            return false;
        } elseif ($payload['posId'] !== (int)$config['shop_id']) {
            return false;
        } elseif ((string)$payload['amount'] !== $totalAmount) {
            return false;
        } elseif ($payload['currency'] !== $order->getOrderCurrencyCode()) {
            return false;
        } elseif ($this->sign($payload, $config['salt']) !== $payload['sign']) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * External verify.
     *
     * @param Order $order
     * @param array $statusPayload
     * @param array $config
     * @return bool
     */
    private function externalVerify($order, $statusPayload, $config)
    {
        $totalAmount = (int)number_format($order->getGrandTotal() * 100, 0, "", "");
        $transactionService = new RestTransaction($this->scopeConfig, $order->getStoreId());
        $payload = new PayloadForRestTransactionVerify();
        $payload->merchantId = (int)$config['merchant_id'];
        $payload->posId = (int)$config['shop_id'];
        $payload->sessionId = $statusPayload['sessionId'];
        $payload->amount = $totalAmount;
        $payload->currency = $order->getOrderCurrencyCode();
        $payload->orderId = $statusPayload['orderId'];
        $verified = $transactionService->verify($payload);

        return $verified;
    }

    /**
     * On success.
     *
     * @param Order $order
     * @return bool
     */
    private function onSuccess($order)
    {
        $storeId = $order->getStoreId();
        $chgState = $this->scopeConfig->getValue(Data::XML_PATH_CHG_STATE, ScopeInterface::SCOPE_STORE, $storeId);
        $mkInvoice = $this->scopeConfig->getValue(Data::XML_PATH_MK_INVOICE, ScopeInterface::SCOPE_STORE, $storeId);

        $sendOrderUpdateEmail = false;
        $orderRepo = $this->objectManager->get(OrderRepositoryInterface::class);
        if (1 === (int)$chgState) {
            if ($order->getState() != Order::STATE_PROCESSING) {
                $sendOrderUpdateEmail = true;
            }
            $msg = __('The payment has been accepted.');
            $order->addStatusToHistory(Order::STATE_PROCESSING, $msg, true);
            $order->setState(Order::STATE_PROCESSING);
            $orderRepo->save($order);
            if (1 === (int)$mkInvoice) {
                $this->makeInvoice($order);
            } else {
                $order->setTotalPaid($order->getGrandTotal());
            }
        }
        $orderRepo->save($order);
        $this->objectManager->get(Order::class);

        return $sendOrderUpdateEmail;
    }

    /**
     * On failure.
     *
     * @param Order $order
     * @return bool
     */
    private function onFailure($order)
    {
        if ($order->getState() !== Order::STATE_HOLDED) {
            $sendOrderUpdateEmail = true;
        }

        $order->addStatusToHistory(Order::STATE_HOLDED, __('Payment error.'), true);
        $order->setState(Order::STATE_HOLDED);

        return $sendOrderUpdateEmail;
    }

    /**
     * Make invoice.
     *
     * @param Order $order
     */
    private function makeInvoice($order)
    {
        try {
            if ($order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                if ($invoice->getTotalQty()) {
                    $invoice->register();
                    $transactionSave = $this->objectManager->create('Magento\Framework\DB\Transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transactionSave->save();
                }
            }
        } catch (Exception $ex) {
            error_log(__METHOD__ . ' ' . $ex->getMessage());
        }
    }
}
