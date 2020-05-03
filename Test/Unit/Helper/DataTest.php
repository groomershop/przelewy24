<?php

namespace Dialcom\Przelewy\Test\Unit\Helper;

use Dialcom\Przelewy\Helper\Data;

class DataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_scopeConfigMock;

    /**
     * @var \Dialcom\Przelewy\Helper\Data
     */
    protected $_helper;

    protected function setUp()
    {
        $className = 'Dialcom\Przelewy\Helper\Data';
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $arguments = $objectManager->getConstructArguments($className);
        $this->_helper = $objectManager->getObject($className, $arguments);
        /** @var \Magento\Framework\App\Helper\Context $context */
        $context = $arguments['context'];
        $this->_scopeConfigMock = $context->getScopeConfig();
    }

    /**
     * @test
     * @param string $xmlPath
     * @param string $returnValue
     * @dataProvider dataProviderForGetConfigShouldReturnString
     */
    public function getConfigShouldReturnString($xmlPath, $returnValue)
    {
        $this->_scopeConfigMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            $xmlPath
        )->will(
            $this->returnValue($returnValue)
        );

        $this->assertEquals($returnValue, $this->_helper->getConfig($xmlPath));
    }

    /**
     * @test
     */
    public function getStoreNameShouldReturnString()
    {
        $name = 'MagentoStore';
        $this->_scopeConfigMock->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            'general/store_information/name'
        )->will(
            $this->returnValue($name)
        );

        $this->assertEquals($name, $this->_helper->getStoreName());
    }

    /**
     * @return array
     */
    public function dataProviderForGetConfigShouldReturnString()
    {
        return [
            [Data::XML_PATH_MERCHANT_ID, 12345],
            [Data::XML_PATH_API_KEY, 'qeqw476qweq5234szwqe5wq4adsa3453'],
            [Data::XML_PATH_CHG_STATE, 0],
            [Data::XML_PATH_EXTRACHARGE, 1],
            [Data::XML_PATH_EXTRACHARGE_AMOUNT, 14.5],
            [Data::XML_PATH_EXTRACHARGE_PERCENT, 30],
            [Data::XML_PATH_GA_BEFORE_PAYMENT, 0],
            [Data::XML_PATH_GA_KEY, 'UA-12345678-9'],
            [Data::XML_PATH_INSTALLMENT, 0],
            [Data::XML_PATH_IVR, 0],
            [Data::XML_PATH_MK_INVOICE, 1],
            [Data::XML_PATH_MODE, 1],
            [Data::XML_PATH_SHOWPAYMENTMETHODS, 1],
            [Data::XML_PATH_WAIT_FOR_RESULT, 1],
            [Data::XML_PATH_USEGRAPHICAL, 1],
            [Data::XML_PATH_P24REGULATIONS, 1],
            [Data::XML_PATH_ONECLICK, 0],
            [Data::XML_PATH_PAYSLOW, 0],
            [Data::XML_PATH_TEXT, 'some description'],
            [Data::XML_PATH_SHOP_ID, 54321],
            [Data::XML_PATH_TIMELIMIT, 15],
            [Data::XML_PATH_SALT, '1234567898765432']
        ];
    }
}
