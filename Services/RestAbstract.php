<?php
namespace Dialcom\Przelewy\Services;

use Dialcom\Przelewy\Helper\Data;

/**
 * Class RestAbstract
 */
class RestAbstract
{
    const URL_PRODUCTION = 'https://secure.przelewy24.pl/api/v1';
    const URL_TEST = 'https://sandbox.przelewy24.pl/api/v1';

    /**
     * Shop id.
     *
     * @var int|null
     */
    protected $shopId;

    /**
     * Api key.
     *
     * @var string|null
     */
    protected $apiKey;

    /**
     * Url.
     *
     * @var string|null
     */
    protected $url;

    /**
     * Salt.
     *
     * @var string|null
     */
    protected $salt;

    /**
     * Przelewy24RestAbstract constructor.
     *
     * @param $currencySuffix
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $magentoStoreId
    ) {
        $this->shopId = (int)$this->getConfigValue($scopeConfig, Data::XML_PATH_SHOP_ID, $magentoStoreId);
        $this->apiKey = (string)$this->getConfigValue($scopeConfig, Data::XML_PATH_API_KEY, $magentoStoreId);
        $this->salt = (string)$this->getConfigValue($scopeConfig, Data::XML_PATH_SALT, $magentoStoreId);
        $isTest = (string)$this->getConfigValue($scopeConfig, Data::XML_PATH_MODE, $magentoStoreId) === '1';
        if ($isTest) {
            $this->url = self::URL_TEST;
        } else {
            $this->url = self::URL_PRODUCTION;
        }
    }

    /**
     * Get config value.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param $configId
     * @param $magentoStoreId
     * @return mixed
     */
    private function getConfigValue(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $configId,
        $magentoStoreId
    ) {
        return $scopeConfig->getValue($configId, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $magentoStoreId);
    }

    /**
     * Call rest command
     *
     * @param string $path
     * @param array|object $payload
     * @return string
     */
    protected function call($path, $payload, $method)
    {
        $headers = [
            'Content-Type: application/json',
        ];
        $options = [
            CURLOPT_USERPWD => $this->shopId . ':' . $this->apiKey,
            CURLOPT_URL => $this->url . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE, JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ('PUT' === $method) {
            $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        }

        $h = curl_init();
        curl_setopt_array($h, $options);
        $ret = curl_exec($h);
        curl_close($h);

        $decoded = json_decode($ret, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return $decoded;
    }
}
