<?php


namespace Dialcom\Przelewy\Observer;

use Magento\Framework\App\Cache;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Observer to clear cache.
 */
class CacheClearObserver implements ObserverInterface
{
    const CACHE_PAGE_CACHE = 'full_page';
    const EVENT_DATA_KEY_CHANGED_PATHS = 'changed_paths';
    const P24_SETTINGS_INSTALLMENT = 'przelewy_settings/paysettings/installment';

    /**
     * @var Cache\TypeListInterface
     */
    private $cacheTypeList;

    /**
     * CacheClearObserver constructor.
     *
     * @param Cache\TypeListInterface $cacheTypeList
     */
    public function __construct(Cache\TypeListInterface $cacheTypeList)
    {
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Entry point for observer.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $changed = $observer->getDataByKey(self::EVENT_DATA_KEY_CHANGED_PATHS);
        if ($changed && in_array(self::P24_SETTINGS_INSTALLMENT, $changed)) {
            $this->cacheTypeList->cleanType(self::CACHE_PAGE_CACHE);
        }
    }
}
