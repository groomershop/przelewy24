<?php
namespace Dialcom\Przelewy\Services;

/**
 * Class PayloadForRestTransaction
 */
class PayloadForRestTransaction
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
     * Description.
     *
     * @var string|null
     */
    public $description;

    /**
     * Email.
     *
     * @var string|null
     */
    public $email;

    /**
     * Client.
     *
     * @var string|null
     */
    public $client;

    /**
     * Address.
     *
     * @var string|null
     */
    public $address;

    /**
     * Zip.
     *
     * @var string|null
     */
    public $zip;

    /**
     * City.
     *
     * @var string|null
     */
    public $city;

    /**
     * Country.
     *
     * @var string|null
     */
    public $country;

    /**
     * Language.
     *
     * @var string|null
     */
    public $language;

    /**
     * Url return.
     *
     * @var string|null
     */
    public $urlReturn;

    /**
     * Url status.
     *
     * @var string|null
     */
    public $urlStatus;

    /**
     * Regulation accept.
     *
     * @var bool|null
     */
    public $regulationAccept;

    /**
     * Shipping.
     *
     * @var integer|null
     */
    public $shipping;

    /**
     * Sign.
     *
     * @var string|null
     */
    public $sign;

    /**
     * Encoding.
     *
     * @var string|null
     */
    public $encoding;

    /**
     * Method ref id.
     *
     * @var string|null
     */
    public $methodRefId;
}
