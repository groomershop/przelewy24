<?php

namespace Dialcom\Przelewy\Model\ResourceModel;

class Recurring extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Recurring constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     */
    public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context)
    {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('p24_recurring', 'id');
    }
}
