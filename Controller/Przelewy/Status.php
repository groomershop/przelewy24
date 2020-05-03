<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

class Status extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dialcom\Przelewy\Helper\Data
     */
    protected $helper;

    /**
     * Status constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Dialcom\Przelewy\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Dialcom\Przelewy\Helper\Data $helper
    )
    {
        $this->helper = $helper;
        parent::__construct($context);
    }


    /**
     * @return void
     */
    public function execute()
    {
        $result = false;
        $sessionId = substr($this->getRequest()->getPost('p24_session_id', null), 0, 100);

        if (isset($sessionId)) {
            $sa_sid = explode('|', $sessionId);
            $order_id = isset($sa_sid[0]) ? (int) $sa_sid[0] : null;

            if (!is_null($order_id)) {
                $result = $this->helper->verifyTransaction($order_id);
            }
        }

        echo $result ? 'OK' : 'ERROR';
        exit;
    }
}
