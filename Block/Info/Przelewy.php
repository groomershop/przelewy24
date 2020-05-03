<?php

namespace Dialcom\Przelewy\Block\Info;

use Dialcom\Przelewy\Helper\Data;
use Dialcom\Przelewy\Model\Recurring;

class Przelewy extends \Magento\Payment\Block\Info
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Przelewy constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    )
    {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
    }

    public function getDescription()
    {
        return __($this->scopeConfig->getValue(Data::XML_PATH_TEXT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
    }

    public function getSpecificInformation()
    {
        $info = $this->getInfo()->_data['additional_information'];
        $ret = array();

        if (!empty($info['method_name'])) $ret['Metoda'] = $info['method_name'];
        if (!empty($info['cc_name'])) $ret['Karta'] = $info['cc_name'];

       /* if (isset($info['p24_forget'])) {
            Recurring::setP24Forget($info['p24_forget'] == '1');
        } */

        return $ret;
    }
}
