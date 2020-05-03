<?php

namespace Dialcom\Przelewy\Block\Adminhtml\Order\View\Tab;

use Dialcom\Przelewy\Helper\Data;

class Refunds extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registryObject;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    private $merchantId;
    private $apiKey;
    private $crc;
    private $sandbox = false;
    private $order;

    /**
     * Refunds constructor.
     * @param \Magento\Framework\Registry $registryObject
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registryObject,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->scopeConfig = $context->getScopeConfig();
        $this->registryObject = $registryObject;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->merchantId = $this->scopeConfig->getValue(Data::XML_PATH_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->crc = $this->scopeConfig->getValue(Data::XML_PATH_SALT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->apiKey = $this->scopeConfig->getValue(Data::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->sandbox = $this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == '1';
    }

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('dialcom/przelewy/order/view/tab/refunds.phtml');
    }

    public function getTabLabel()
    {
        return __('Przelewy24 - Refunds');
    }

    public function getTabTitle()
    {
        return __('Przelewy24 - Refunds');
    }

    public function canShowTab()
    {
        return $this->getPaymentMethod() === 'dialcom_przelewy';
    }

    public function isHidden()
    {
        return false;
    }

    public function getOrder()
    {
        $orderFromRegistry = $this->registryObject->registry('current_order');
        if (!is_null($orderFromRegistry)) {
            $this->order = $orderFromRegistry;
            return $this->order;
        }

        return $this->order;
    }

    /**
     * @param $orderId
     */
    public function setOrder($orderId)
    {
        $this->order = $this->objectManager->create('Magento\Sales\Model\Order')->load($orderId);
    }

    private function getPaymentMethod()
    {
        return $this->getOrder()->getPayment()->getMethodInstance()->getCode();
    }

    public function isSoapExtensionInstalled()
    {
        return extension_loaded('soap');
    }

    /**
     * @return array
     */
    public function getRefunds()
    {
        $result = array(
            'amount' => $this->getOrder()->getGrandTotal(),
            'refunds' => array()
        );

        $refunds = array();

        try {
            $url = $this->getWSUrl();
            $sessionId = substr($this->getOrder()->getData('p24_session_id'), 0, 100);
            $p24OrderId = (int) $this->getOrderIdBySessionId($sessionId);
            $soap = new \SoapClient($url, array('cache_wsdl' => WSDL_CACHE_NONE));
            $wsResult = $soap->GetRefundInfo($this->merchantId, $this->apiKey, $p24OrderId);

            if (!empty($wsResult->result)) {
                $refunds['maxToRefund'] = 0;
                foreach ($wsResult->result as $key => $value) {
                    $refunds['refunds'][$key]['amount_refunded'] = $value->amount;
                    $date = new \DateTime($value->date);
                    $refunds['refunds'][$key]['created'] = $date->format('Y-m-d H:i:s');
                    $refunds['refunds'][$key]['status'] = $this->getStatusMessage($value->status);

                    if ($value->status === 1 || $value->status === 3) {
                        $refunds['maxToRefund'] += $value->amount;
                    }
                }
            }

            if (!empty($refunds)) {
                $result['amount'] -= ($refunds['maxToRefund'] / 100);
                $result['refunds'] = $refunds['refunds'];
            }
        } catch (\Exception $e) {
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getWSUrl()
    {
        $mode = $this->sandbox ? 'sandbox' : 'secure';
        $url = 'https://' . $mode . '.przelewy24.pl/external/' . $this->merchantId . '.wsdl';

        return $url;
    }

    /**
     * @param $status
     * @return string
     */
    private function getStatusMessage($status)
    {
        switch ($status) {
            case 0:
                $statusMessage = __('Error');
                break;
            case 1:
                $statusMessage = __('Completed');
                break;
            case 2:
                $statusMessage = __('Suspended');
                break;
            case 3:
                $statusMessage = __('Pending');
                break;
            case 4:
                $statusMessage = __('Rejected');
                break;
            default:
                $statusMessage = __('Unknown status');
        }

        return $statusMessage;
    }

    /**
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('przelewyadmin/przelewy/refunds', array('order_id' => $this->getOrder()->getId()));
    }


    /**
     * @param $orderId
     * @param $amountToRefund
     * @return string
     */
    public function refundProcess($orderId, $amountToRefund)
    {
        $orderId = (int) $orderId;
        $this->setOrder($orderId);
        $storeId = $this->order->getStoreId();
        $this->merchantId = $this->scopeConfig->getValue(Data::XML_PATH_MERCHANT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $this->crc = $this->scopeConfig->getValue(Data::XML_PATH_SALT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $this->apiKey = $this->scopeConfig->getValue(Data::XML_PATH_API_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        $this->sandbox = $this->scopeConfig->getValue(Data::XML_PATH_MODE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId) == '1';

        $sessionId = substr($this->getOrder()->getData('p24_session_id'), 0, 100);

        $refunds = array(
            0 => array(
                'sessionId' => $sessionId,
                'orderId' => $this->getOrderIdBySessionId($sessionId),
                'amount' => $amountToRefund * 100
            )
        );

        $response = $this->refundTransaction($refunds);
        $result = $this->prepareRefundResponse($response);

        return $result;
    }

    /**
     * @param $refunds
     * @return string
     */
    private function refundTransaction($refunds)
    {
        try {
            $url = $this->getWSurl();

            $soap = new \SoapClient($url);
            $response = $soap->refundTransaction(
                $this->merchantId,
                $this->apiKey,
                time(),
                $refunds
            );

            return $response;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $refundResponse
     * @return array
     */
    private function prepareRefundResponse($refundResponse)
    {
        try {
            $result = array(
                'error' => true,
                'success' => false,
                'message' => __('Refund processing error!'),
            );

            if (isset($refundResponse->result)) {
                foreach ($refundResponse->result as $key => $value) {
                    if ((int)$value->status === 1) {
                        $result['error'] = false;
                        $result['success'] = true;
                        $result['message'] = __('Refund was successful!');
                    } else {
                        if (isset($value->error)) {
                            $result['message'] = $value->error;
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            $result = array(
                'message' => __('Refund processing error!')
            );
        }

        return $result;
    }

    /**
     * @param $allowedAmount
     * @return string
     */
    public function buildRefundsForm($allowedAmount)
    {
        if ($allowedAmount > 0) {
            $form = '<label for="amountToRefund">' . __('Amount') . '</label>
                  <input id="amountToRefund" type="number" name="amountToRefund"
                         value="' . $allowedAmount . '" min="0.01" max="' . $allowedAmount . '" step="0.01"/>
                  <input type="hidden" id="maxAmount" name="maxAmount" value="' . $allowedAmount . '"/>';
        } else {
            $form = '<div class="message message-warning warning">' .
                __('The payment has already been fully refunded - no funds to make further returns.') .
                '</div>';
        }

        return $form;
    }

    /**
     * @param $refunds
     * @return string
     */
    public function buildRefundsTable($refunds)
    {
        if (empty($refunds)) {
            return '<div class="message message-notice notice" id="refundsListErrorMessage">' .
            __('There is no refunds.') .
            '</div>';
        }

        $dateLabel = __('Date of refund');
        $amountLabel = __('Amount refunded');
        $table = <<< HTML
                        <colgroup>
                            <col width="10%">
                            <col width="45%">
                            <col width="35%">
                            <col width="10%">
                        </colgroup>
                        <thead>
                        <tr class="headings">
                            <th class="a-center">
                                <span class="nobr">L.p.</span></th>
                            <th class="a-center">
                                <span class="nobr">{$dateLabel}</span>
                            </th>
                            <th class="a-center">
                                <span class="nobr">{$amountLabel}</span>
                            </th>
                            <th class="a-center">
                                <span class="nobr">Status</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
HTML;

        foreach ($refunds as $key => $refund) {
            $lp = $key + 1;
            $amount = $this->formatCurrency(($refund['amount_refunded'] / 100));
            $table .= <<< HTML
                            <tr class="border">
                                <td class="a-center">{$lp}</td>
                                <td class="a-center">{$refund['created']}</td>
                                <td class="a-center"><strong>{$amount}</strong></td>
                                <td class="a-center">{$refund['status']}</td>
                            </tr>
HTML;
        }
        $table .= <<< HTML
                        </tbody>
                        <div id="refundsListErrorMessage"></div>
HTML;

        return $table;
    }

    /**
     * @param $amount
     * @return mixed
     */
    public function formatCurrency($amount)
    {
        return $this->objectManager->create('Magento\Framework\Pricing\Helper\Data')->currency($amount, true, false);
    }

    /**
     * @param $sessionId
     * @return int
     */
    private function getOrderIdBySessionId($sessionId)
    {
        $orderId = 0;
        try {
            $url = $this->getWSurl();
            $soap = new \SoapClient($url);
            $response = $soap->GetTransactionBySessionId(
                $this->merchantId,
                $this->apiKey,
                substr($sessionId, 0, 100)
            );

            if (isset($response->result) && isset($response->result->orderId)) {
                $orderId = (int) $response->result->orderId;
            }

        } catch (\Exception $e) {
        }

        return $orderId;
    }
}