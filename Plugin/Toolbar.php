<?php

namespace Dialcom\Przelewy\Plugin;

use Dialcom\Przelewy\Helper\Data;

class Toolbar
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Backend\Block\Widget\Button\ButtonList
     */
    private $buttonList;

    /**
     * @var integer
     */
    private $orderId;

    /**
     * Toolbar constructor.
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * @param \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject
     * @param \Magento\Framework\View\Element\AbstractBlock $context
     * @param \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
     * @return array
     */
    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    )
    {
        if (!$context instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            return [$context, $buttonList];
        }

        $this->buttonList = $buttonList;
        $this->orderId = $this->request->getParam('order_id');
        $order = $this->objectManager->create('Magento\Sales\Model\Order')->load($this->orderId);

        if ($order && $order->getBaseTotalDue() > 0) { // jeśli jest coś jeszcze do zapłacenia to pokaż przyciski
            $this->getLinkButton($order);
            $this->getIvrButton($order);
        }

        return [$context, $this->buttonList];
    }

    private function getLinkButton($order)
    {
        $storeId = $order->getStoreId();
        $template = $this->scopeConfig->getValue(Data::XML_PATH_SENDLINK_MAILTEMPLATE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);

        if (!$order->isCanceled() && !empty($template)) {
            $message = __('Are you sure you want to send the customer an e-mail link and start the payment process with Przelewy24?');
            $this->buttonList->add(
                'p24_link', array(
                    'label' => __('Send an e-mail with the link for P24'),
                    'onclick' => 'confirmSetLocation(\'' . $message . '\', \'' . $this->getPaymentEmailUrl() . '\')',
                )
            );
        }
    }

    private function getIvrButton($order)
    {
        $storeId = $order->getStoreId();
        $ivr = (int)$this->scopeConfig->getValue(Data::XML_PATH_IVR, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        if (!$order->isCanceled() && $ivr) {
            $message = __('Are you sure you want to start the payment process by Przelewy24 IVR and call the customer back?');
            $this->buttonList->add(
                'p24_ivr', array(
                    'label' => __('IVR payment with P24'),
                    'onclick' => 'confirmSetLocation(\'' . $message . '\', \'' . $this->getPaymentIvrUrl() . '\')',
                )
            );
        }
    }

    private function getPaymentEmailUrl()
    {
        return $this->urlBuilder->getUrl('przelewyadmin/przelewy/paymentEmail', array('order_id' => $this->orderId));
    }

    private function getPaymentIvrUrl()
    {
        return $this->urlBuilder->getUrl('przelewyadmin/przelewy/paymentIvr', array('order_id' => $this->orderId));
    }
}