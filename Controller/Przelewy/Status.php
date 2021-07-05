<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

use Dialcom\Przelewy\Helper\RestStatusSupport;

class Status extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dialcom\Przelewy\Helper\Data
     */
    protected $helper;

    /**
     * Rest support.
     *
     * @var RestStatusSupport
     */
    protected $restSupport;

    /**
     * Status constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Dialcom\Przelewy\Helper\Data $helper
     * @param RestStatusSupport $restSupport
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Dialcom\Przelewy\Helper\Data $helper,
        RestStatusSupport $restSupport
    )
    {
        $this->helper = $helper;
        $this->restSupport = $restSupport;
        parent::__construct($context);
    }


    /**
     * @return void
     */
    public function execute()
    {
        $result = false;
        $request = $this->getRequest();
        if ($request->getParam('response', false) === 'rest') {
            $result = $this->restSupport->checkStatus();
        } else {
            $sessionId = substr($this->getRequest()->getPost('p24_session_id', null), 0, 100);
            $sa_sid = explode('|', $sessionId);
            $order_id = isset($sa_sid[0]) ? (int) $sa_sid[0] : null;
            if ($order_id) {
                $result = $this->helper->verifyTransaction($order_id);
            }
        }

        echo $result ? 'OK' : 'ERROR';
        exit;
    }
}
