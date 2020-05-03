<?php

namespace Dialcom\Przelewy\Model\Config;

class Product
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;

    /**
     * Product constructor.
     */
    public function __construct()
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    public function toOptionArray()
    {
        $productCollection = $this->objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory')
            ->create()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('type_id', array('eq' => 'virtual'))
            ->load();


        $result = array();
        foreach ($productCollection as $product) {
            $result[] = array('value' => (int) $product->getId(), 'label' => filter_var($product->getName(), FILTER_SANITIZE_STRING));
        }
        return $result;
    }
}
