<?php

namespace Dialcom\Przelewy\Controller\Przelewy;

use Dialcom\Przelewy\Model\Recurring;

class MyCards extends \Magento\Framework\App\Action\Action
{
    /**
     * MyCards constructor.
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    )
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $customerSession = $this->_objectManager->get('Magento\Customer\Model\Session');

        if ($customerSession->isLoggedIn()) {
            $oneclick = $this->_url->getUrl('przelewy/przelewy/oneclick');
            if (!$oneclick) return $this->_redirect('customer/account');

            $cardRm = $this->_request->getParam('cardrm');
            if ($cardRm > 0) {
                try {
                    Recurring::unregisterCard($cardRm);
                    $this->messageManager->addSuccess(__('Card has been deleted!'));
                } catch (\Exception $ex) {
                    $this->messageManager->addError(__('An error occured during card deleting!'));
                    error_log(__METHOD__ . ' ' . $ex->getMessage());
                }

            }

            $cardForgetAction = $this->_request->getParam('cardforget');
            if ($cardForgetAction > 0) {
                try {
                    // param p24_forget == 0 or 1
                    $p24_forget = (int)$this->_request->getParam('p24_forget');
                    Recurring::setP24Forget($p24_forget);
                    $this->messageManager->addSuccess(__('Settings have been saved!'));
                } catch (\Exception $ex) {
                    $this->messageManager->addError(__('An error occured during saving!'));
                    error_log(__METHOD__ . ' ' . $ex->getMessage());
                }
            }

            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->set(__('Przelewy24 - My Credit Cards'));
            $this->_view->renderLayout();
        } else {
            return $this->_redirect('customer/account');
        }
    }
}
