<?php

namespace Dialcom\Przelewy\Test\Unit\Model\Config;

use Dialcom\Przelewy\Model\Config\Channels;

class ChannelsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function getChannelsInstallmentShouldReturnArray()
    {
        $type = 'array';
        $actual = Channels::getChannelsInstallment();

        $this->assertInternalType($type, $actual, 'getChannelsInstallment - return type is not an ' . $type);
    }

    /**
     * @test
     */
    public function getChannelsNonPlnShouldReturnArray()
    {
        $type = 'array';
        $actual = Channels::getChannelsNonPln();

        $this->assertInternalType($type, $actual, 'getChannelsNonPln - return type is not an ' . $type);
    }

    /**
     * @test
     */
    public function getChannelsInstallmentShouldReturnArrayWithGivenValues()
    {
        $expected = array(72, 129, 136);
        $actual = Channels::getChannelsInstallment();

        $this->assertEquals($expected, $actual, 'getChannelsInstallment static method failed');
    }

    /**
     * @test
     */
    public function getChannelsNonPlnShouldReturnArrayWithGivenValues()
    {
        $expected = array(66, 92, 124, 140, 145, 152, 218);
        $actual = Channels::getChannelsNonPln();

        $this->assertEquals($expected, $actual, 'getChannelsNonPln static method failed');
    }
}
