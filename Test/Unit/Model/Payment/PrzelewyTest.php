<?php

namespace Dialcom\Przelewy\Test\Unit\Model\Payment;

use Dialcom\Przelewy\Helper\Data;

class PrzelewyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $helper;
    /**
     * @var \Dialcom\Przelewy\Model\Payment\Przelewy
     */
    protected $model;

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManagerBackup;

    /**
     * @var \Magento\Framework\App\ObjectManager| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfig;


    protected function setUp()
    {
        $this->helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $objectManagerMock = $this->getMock('Magento\Framework\ObjectManagerInterface');

        try {
            $this->objectManagerBackup = \Magento\Framework\App\ObjectManager::getInstance();
        } catch (\RuntimeException $e) {
            $this->objectManagerBackup = \Magento\Framework\App\Bootstrap::createObjectManagerFactory(BP, $_SERVER)
                ->create($_SERVER);
        }
        \Magento\Framework\App\ObjectManager::setInstance($objectManagerMock);

        $arguments = $this->helper->getConstructArguments('Magento\Framework\App\ObjectManager');
        $this->objectManager = $this->getMock('Magento\Framework\App\ObjectManager', [], $arguments);
        $scopeConfigArguments = $this->helper->getConstructArguments('Magento\Framework\App\Config\ScopeConfigInterface');
        $this->scopeConfig = $this->getMock('Magento\Framework\App\Config\ScopeConfigInterface', ['getValue', 'isSetFlag'], $scopeConfigArguments);

        $this->model = $this->helper->getObject(
            'Dialcom\Przelewy\Model\Payment\Przelewy',
            [
                'objectManager' => $this->objectManager,
                'scopeConfig' => $this->scopeConfig
            ]
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Magento\Framework\App\ObjectManager::setInstance($this->objectManagerBackup);
    }

    /**
     * @test
     */
    public function getCountriesToOptionArrayShouldReturnArray()
    {
        $type = 'array';
        $actual = $this->model->getCountriesToOptionArray();

        $this->assertInternalType($type, $actual, 'Dialcom\Przelewy\Model\Payment\Przelewy getCountriesToOptionArray() not return ' . $type);
    }

    /**
     * @test
     * @dataProvider amountDataProvider
     */
    public function getExtrachargeAmountTestedByAmount($input, $expected)
    {
        $orderId = '000000040';
        $total = '754';
        $arguments = $this->helper->getConstructArguments('Magento\Sales\Model\Order');
        $order = $this->getMock('Magento\Sales\Model\Order', [], $arguments);

        $this->objectManager->expects($this->any())->method('create')->willReturn($order);

        $order->expects($this->any())
            ->method('loadByIncrementId')
            ->with($orderId)
            ->willReturn($order);
        $order->expects($this->any())
            ->method('getGrandTotal')
            ->willReturn($total);

        $this->getScopeConfig($input, 0);

        $actual = $this->model->getExtrachargeAmount($orderId);

        $this->assertEquals($expected, $actual);
    }

    /**
     * test
     * dataProvider amountDataProvider
     */
    public function getExtrachargeAmountByAmountTestedByAmount($input, $expected)
    {
        $total = '754';
        $this->getScopeConfig($input, 0);
        $actual = $this->model->getExtrachargeAmountByAmount($total);

        $this->assertEquals($expected, $actual);
    }

    public function amountDataProvider()
    {
        return [
            [12, 12 * 100],
            [12.45, 12.45 * 100],
            [1435, 1435 * 100],
            [75.34, 75.34 * 100],
            [234.65, 234.65 * 100],
            [-2342, 0],
            [0, 0]
        ];
    }

    private function getScopeConfig($amount, $percent)
    {
        $this->scopeConfig->expects($this->at(0))
            ->method('getValue')
            ->with(Data::XML_PATH_EXTRACHARGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn(1);
        $this->scopeConfig->expects($this->at(1))
            ->method('getValue')
            ->with(Data::XML_PATH_EXTRACHARGE_PRODUCT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn(1);
        $this->scopeConfig->expects($this->at(2))
            ->method('getValue')
            ->with(Data::XML_PATH_EXTRACHARGE_AMOUNT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn($amount);
        $this->scopeConfig->expects($this->at(3))
            ->method('getValue')
            ->with(Data::XML_PATH_EXTRACHARGE_PERCENT, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn($percent);
    }
}
