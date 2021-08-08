<?php

namespace Dialcom\Przelewy\Model\Config;

class Installment
{
    const SHOW_NOT = 0;
    const SHOW_GATEWAY_ONLY = 1;
    const SHOW_ALL = 2;

    public function toOptionArray()
    {
        return array(
            array('value' => self::SHOW_ALL, 'label' => __('Product page (information) and payment page (button)')),
            array('value' => self::SHOW_GATEWAY_ONLY, 'label' => __('Only payment page (button)')),
            array('value' => self::SHOW_NOT, 'label' => __('Do not show the installment')),
        );
    }
}
