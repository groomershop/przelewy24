<?php
namespace Dialcom\Przelewy\Services;

/**
 * Class PayloadForRestTransactionVerify
 */
class PayloadForRestTransactionVerify
{
    /**
     * Merchant id.
     *
     * @var int|null
     */
    public $merchantId;

    /**
     * Pos id.
     *
     * @var integer
     */
    public $posId;

    /**
     * Session id.
     *
     * @var string|null
     */
    public $sessionId;

    /**
     * Amount.
     *
     * @var int|null
     */
    public $amount;

    /**
     * Currency.
     *
     * @var string|null
     */
    public $currency;

    /**
     * Order id.
     *
     * @var integer|null
     */
    public $orderId;

    /**
     * Sign.
     *
     * @var string|null
     */
    public $sign;
}
