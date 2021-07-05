<?php
namespace Dialcom\Przelewy\Services;

/**
 * Class Przelewy24ReastCard
 */
class RestCard extends RestAbstract
{
    /**
     * Charge with3ds.
     *
     * @param $token
     * @return array
     */
    public function chargeWith3ds($token)
    {
        $path = '/card/chargeWith3ds';
        $payload = [
            'token' => $token,
        ];
        $ret = $this->call($path, $payload, 'POST');

        return $ret;
    }
}
