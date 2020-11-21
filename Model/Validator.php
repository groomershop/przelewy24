<?php

namespace Dialcom\Przelewy\Model;

use Dialcom\Przelewy\Przelewy24Class;
use Magento\Framework\Phrase;

/**
 * Description of Validator
 *
 * @author adamm
 */
class Validator extends \Magento\Framework\App\Config\Value
{
    /**
     * Validator constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function beforeSave()
    {
        if ((int)$this->getFieldsetDataValue('active') == 1) {
            $path = $this->getPath();
            $field = substr($path, strrpos($path, '/') + 1);
            if ($field == 'merchant_id') {
                $val = (int)$this->getValue();
                if ($val < 1000) {
                    $phrase = new Phrase(__('Przelewy24: Incorrect seller ID'));
                    throw new \Magento\Framework\Exception\LocalizedException($phrase);
                }
            } elseif ($field == 'shop_id') {
                $val = (int)$this->getValue();
                if ($val < 1000) {
                    $phrase = new Phrase(__('Przelewy24: Incorrect shop ID'));
                    throw new \Magento\Framework\Exception\LocalizedException($phrase);
                }
            } elseif ($field == 'salt') {
                $value = $this->getValue();
                if (strlen($value) != 16 || !ctype_xdigit($value)) {
                    $phrase = new Phrase(__('Przelewy24: The CRC key must have 16 characters'));
                    throw new \Magento\Framework\Exception\LocalizedException($phrase);
                }
            } elseif ($field == 'mode') {
                $settings = $this->getFieldsetData();
                $P24 = new Przelewy24Class($settings['merchant_id'], $settings['shop_id'], $settings['salt'], ($settings['mode'] == 1));
                $ret = $P24->testConnection();
                if ($ret['error'] != 0) {
                    $phrase = new Phrase(__('Przelewy24: Bad Shop ID, Seller or CRC Key for this plug-in mode'));
                    throw new \Magento\Framework\Exception\LocalizedException($phrase);
                }
            }
        }
        parent::beforeSave();
    }
}
