<?php

namespace Dialcom\Przelewy\Test\Unit\Model\Config;

use Dialcom\Przelewy\Model\Config\Installment;

class InstallmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function toOptionArrayShouldReturnArray()
    {
        $type = 'array';
        $installment = new Installment();
        $actual = $installment->toOptionArray();

        $this->assertInternalType($type, $actual, 'Installment toOptionArray() not return ' . $type);
    }

    /**
     * @test
     */
    public function toOptionArrayShouldReturnArrayWithExpectedValues()
    {
        $expected = $this->getExpectedData();
        $installment = new Installment();
        $actual = $installment->toOptionArray();

        $this->assertEquals($expected, $actual, 'Installment toOptionArray() - expected and actual arrays are not equal');
    }

    /**
     * @return array
     */
    private function getExpectedData()
    {
        return array(
            array('value' => 2, 'label' => __('Product page (information) and payment page (button)')),
            array('value' => 1, 'label' => __('Only payment page (button)')),
            array('value' => 0, 'label' => __('Do not show the installment')),
        );
    }
}
