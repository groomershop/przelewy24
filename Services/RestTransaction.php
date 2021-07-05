<?php
namespace Dialcom\Przelewy\Services;

/**
 * Class RestTransaction
 */
class RestTransaction extends RestAbstract
{
    /**
     * Register.
     *
     * @param PayloadForRestTransaction $payload
     * @return array
     */
    public function register($payload)
    {
        $path = '/transaction/register';
        $this->signSha384ForRegister($payload);

        return $this->call($path, $payload, 'POST');
    }

    /**
     * Verify.
     *
     * @param PayloadForRestTransactionVerify $payload
     * @return string
     */
    public function verify($payload)
    {
        $path = '/transaction/verify';
        $this->signSha384ForVerification($payload);
        $data = $this->call($path, $payload, 'PUT');

        if (isset($data['data']['status'])) {
            return $data['data']['status'] === 'success';
        } else {
            return false;
        }
    }

    /**
     * Sign sha384 for register.
     *
     * @param PayloadForRestTransaction $payload
     */
    private function signSha384ForRegister($payload)
    {
        $data = array(
            'sessionId' => $payload->sessionId,
            'merchantId' => $payload->merchantId,
            'amount' => $payload->amount,
            'currency' => $payload->currency,
            'crc' => $this->salt,
        );
        $string = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $sign = hash('sha384', $string);
        $payload->sign = $sign;
    }

    /**
     * Sign sha384 for verficication.
     *
     * @param PayloadForRestTransactionVerify $payload
     */
    private function signSha384ForVerification($payload)
    {
        $data = array(
            'sessionId' => $payload->sessionId,
            'orderId' => $payload->orderId,
            'amount' => $payload->amount,
            'currency' => $payload->currency,
            'crc' => $this->salt,
        );
        $string = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $sign = hash('sha384', $string);
        $payload->sign = $sign;
    }
}
