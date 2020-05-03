<?php

namespace Dialcom\Przelewy\Test\Unit\Block\Form;

class PrzelewyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $helper;
    /**
     * @var \Dialcom\Przelewy\Block\Form\Przelewy
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
     * @var \Magento\Framework\UrlInterface| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilder;

    /**
     * @var string
     */
    private $urlPattern;

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
        $this->urlBuilder = $this->getMock('Magento\Framework\UrlInterface');

        $this->model = $this->helper->getObject(
            'Dialcom\Przelewy\Block\Form\Przelewy',
            [
                'objectManager' => $this->objectManager,
                'urlBuilder' => $this->urlBuilder
            ]
        );

        $this->urlPattern = '/((http|https)\:\/\/)?[a-zA-Z0-9\.\/\?\:@\-_=#]+\.([a-zA-Z0-9\.\/\?\:@\-_=#])*/';
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Magento\Framework\App\ObjectManager::setInstance($this->objectManagerBackup);
    }

    /**
     * @test
     */
    public function getCardShouldReturnArray()
    {
        $type = 'array';
        $actual = $this->model->getCards();

        $this->assertInternalType($type, $actual, 'Dialcom\Przelewy\Block\Form\Przelewy getCards() not return ' . $type);
    }

    /**
     * @test
     */
    public function p24getCssUrlShouldReturnUrl()
    {
        $arguments = $this->helper->getConstructArguments('Magento\Framework\View\Asset\Repository');
        $asset = $this->getMock('Magento\Framework\View\Asset\Repository', [], $arguments);
        $fileArguments = $this->helper->getConstructArguments('Magento\Framework\View\Asset\File');
        $file = $this->getMock('Magento\Framework\View\Asset\File', [], $fileArguments);
        $this->objectManager->expects($this->any())->method('get')->willReturn($asset);
        $asset->expects($this->any())
            ->method('createAsset')
            ->with('Dialcom_Przelewy::css/css_paymethods.css')
            ->willReturn($file);
        $file->expects($this->any())
            ->method('getUrl')
            ->willReturn('http://magento2/pub/static/frontend/Magento/luma/pl_PL/Dialcom_Przelewy/css/css_paymethods.css');

        $this->assertRegExp($this->urlPattern, $this->model->p24getCssUrl());
    }

    /**
     * @test
     * @dataProvider bankDataProvider
     */
    public function getBankHtmlShouldReturnString($id, $bankName)
    {
        $type = 'string';
        $actual = $this->model->getBankHtml($id, $bankName);

        $this->assertInternalType($type, $actual, 'Dialcom\Przelewy\Block\Form\Przelewy getBankHtml() not return ' . $type);
    }

    /**
     * @param $id
     * @param $bankName
     * @test
     * @dataProvider bankDataProvider
     */
    public function getBankHtmlShouldReturnStringThatContainsValues($id, $bankName)
    {
        $expected = '<a class="bank-box " data-id="' . $id .
            '" data-cc=""><div class="bank-logo bank-logo-' . $id .
            '"></div><div class="bank-name">' . $bankName .
            '</div></a>';
        $actual = $this->model->getBankHtml($id, $bankName);

        $this->assertEquals($expected, $actual, 'Dialcom\Przelewy\Block\Form\Przelewy getBankHtml() not return equal strings');
    }

    /**
     * @test
     * @dataProvider bankDataProvider
     */
    public function getBankTxtShouldReturnString($id, $bankName)
    {
        $type = 'string';
        $actual = $this->model->getBankTxt($id, $bankName);

        $this->assertInternalType($type, $actual, 'Dialcom\Przelewy\Block\Form\Przelewy getBankTxt() not return ' . $type);
    }

    /**
     * @param $id
     * @param $bankName
     * @test
     * @dataProvider bankDataProvider
     */
    public function getBankTxtShouldReturnStringThatContainsValues($id, $bankName)
    {
        $expected = '<li><div class="input-box  bank-item"><input id="przelewy_method_id_' . $id .
            '-" name="payment_method_id" data-id="' . $id .
            '" data-cc="" data-text=""  class="radio" type="radio"  /><label for="przelewy_method_id_' . $id .
            '-">' . $bankName . '</label></div></li>';
        $actual = $this->model->getBankTxt($id, $bankName);

        $this->assertEquals($expected, $actual, 'Dialcom\Przelewy\Block\Form\Przelewy getBankTxt() not return equal strings');
    }

    /**
     * @return array
     */
    public function bankDataProvider()
    {
        return [
            ['129', 'Alior - Raty'],
            ['68', 'Allianz Bank'],
            ['56', 'Bank BGŻ'],
            ['85', 'Bank Millennium'],
            ['32', 'Bank Nordea'],
            ['48', 'Bank Ochrony Środowiska'],
            ['65', 'Bank PEKAO S.A.'],
            ['143', 'Banki Spółdzielcze'],
            ['154', 'BLIK - PSP'],
            ['33', 'BNP Paribas Polska'],
            ['20', 'BZ WBK - Przelew24'],
            ['45', 'Credit Agricole'],
            ['110', 'db Transfer'],
            ['103', 'DnB Nord'],
            ['105', 'E-SKOK'],
            ['141', 'e-transfer Pocztowy24'],
            ['94', 'Euro Bank'],
            ['90', 'FM Bank'],
            ['153', 'Getin Bank'],
            ['145', 'Karta płatnicza'],
            ['218', 'Karta płatnicza'],
            ['25', 'mBank - mTransfer'],
            ['136', 'mBank - Raty'],
            ['113', 'Meritum ePrzelew'],
            ['97', 'mPay'],
            ['27', 'MultiBank - MultiTransfer'],
            ['88', 'Płacę z Alior Bankiem'],
            ['119', 'Płacę z CitiHandlowy'],
            ['135', 'Płacę z IKO'],
            ['26', 'Płacę z Inteligo'],
            ['31', 'Płacę z iPKO (PKO BP)'],
            ['146', 'Płacę z Orange'],
            ['131', 'Płacę z Plus Bank'],
            ['112', 'Płać z ING'],
            ['144', 'Podkarpacki BS'],
            ['82', 'Polbank'],
            ['35', 'Przelew z BPH'],
            ['102', 'Raiffeisen Bank PBL'],
            ['87', 'SkyCash'],
            ['121', 'T-Mobile Usługi Bankowe'],
            ['64', 'Toyota Bank'],
            ['111', 'Trust Pay'],
            ['69', 'Volkswagen Bank'],
            ['1000', 'Przekaz/Przelew tradycyjny'],
            ['2000', 'Użyj przedpłaty']
        ];
    }
}
