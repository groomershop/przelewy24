<?php

namespace Dialcom\Przelewy\Controller\Adminhtml\Przelewy;

class Refunds extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $jsonResultFactory;

    /**
     * Refunds constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
    )
    {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $params = $this->getRequest()->getParams();
        $amountToRefund = $params['amountToRefund'];
        $maxAmount = $params['maxAmount'];
        $minAmount = 0.01;

        if ($amountToRefund > $maxAmount || $amountToRefund < $minAmount || !is_numeric($amountToRefund)) {
            $response = array(
                'error' => true,
                'message' => __('Allowed amount range') . ': ' . $minAmount . ' - ' . $maxAmount
            );
        } else {
            $response = $this->refundsService($params);
        }

        $result->setData($response);

        return $result;
    }

    /**
     * @param $params
     * @return array
     */
    private function refundsService($params)
    {
        try {
            $orderId = (int) $params['order_id'];
            $amountToRefund = $params['amountToRefund'];
            $maxAmount = $params['maxAmount'];
            $allowedAmount = $maxAmount - $amountToRefund;
            $block = $this->_view->getLayout()->createBlock('Dialcom\Przelewy\Block\Adminhtml\Order\View\Tab\Refunds');

            $refundProceed = $this->refundProceed($orderId, $amountToRefund);
            if (!$refundProceed['error'] && $refundProceed['success']) {
                $block->setOrder($orderId);
                $order = $block->getOrder();
                $refunds = $block->getRefunds();
                $allowedAmount = isset($refunds['amount']) ? $refunds['amount'] : ($maxAmount - $amountToRefund);
                $refundsToTable = isset($refunds['refunds']) && is_array($refunds['refunds']) ? $refunds['refunds'] : array();
                $order->setTotalRefunded(round(
                    round($order->getTotalRefunded(), 2) + round($amountToRefund, 2),
                    2
                    ));
                $order->save();
                return array(
                    'error' => false,
                    'message' => $refundProceed['message'],
                    'data' => array(
                        'allowedAmount' => $block->formatCurrency($allowedAmount),
                        'blocked' => $allowedAmount <= 0,
                        'form' => $block->buildRefundsForm($allowedAmount),
                        'table' => $block->buildRefundsTable($refundsToTable)
                    )
                );
            }

            return array(
                'error' => true,
                'message' => $refundProceed['message'],
                'data' => array(
                    'allowedAmount' => $block->formatCurrency($allowedAmount),
                    'blocked' => $allowedAmount <= 0,
                    'form' => $block->buildRefundsForm($allowedAmount),
                    'table' => $block->buildRefundsTable(array())
                )
            );
        } catch (\Exception $ex) {
            return array(
                'error' => true,
                'message' => $ex->getMessage(),
                'data' => array()
            );
        }
    }


    /**
     * @param $orderId
     * @param $amountToRefund
     * @return mixed
     */
    private function refundProceed($orderId, $amountToRefund)
    {
        $block = $this->_view->getLayout()->createBlock('Dialcom\Przelewy\Block\Adminhtml\Order\View\Tab\Refunds');
        $refundResponse = $block->refundProcess($orderId, $amountToRefund);

        return $refundResponse;
    }
}
