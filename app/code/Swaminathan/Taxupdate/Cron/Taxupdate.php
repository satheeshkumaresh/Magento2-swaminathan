<?php
namespace Swaminathan\Taxupdate\Cron;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Eav\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;


class Taxupdate
{
    const MAGENTO_TAX_ATTRIBUTE_CODE = "tax_class_id";

    const AKENEO_TAX_ATTRIBUTE_CODE = "gst_tax_class";

        public function __construct(
                ProductFactory $productFactory,
                Collection $productCollection,
                Config $config,
                StoreManagerInterface $storeManagerInterface,
                ProductRepositoryInterface $productRepositoryInterface

            ) {
                $this->productFactory = $productFactory;
                $this->productCollection = $productCollection;
                $this->config = $config;
                $this->storeManagerInterface = $storeManagerInterface;
                $this->productRepositoryInterface = $productRepositoryInterface;
            }
        public function execute(){
            $collection = $this->productCollection->addAttributeToSelect('*');
            $collection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
            $storeId = $this->storeManagerInterface->getDefaultStoreView()->getId();
            foreach($collection as $data){
                    $data= $this->productFactory->create()->load($data['entity_id']);
                    $product = $this->productRepositoryInterface->getById($data['entity_id']);
                    $gstTaxClass=$data->getResource()->getAttribute(self::AKENEO_TAX_ATTRIBUTE_CODE)->getFrontend()->getValue($data);
                    $taxClass=$data->getResource()->getAttribute(self::MAGENTO_TAX_ATTRIBUTE_CODE)->getFrontend()->getValue($data);
                    $attribute = $this->config->getAttribute('catalog_product', self::MAGENTO_TAX_ATTRIBUTE_CODE);
                    $options = $attribute->getSource()->getAllOptions();
                    if($gstTaxClass != $taxClass){
                            foreach($options as $option){
                            if($option['label'] == $gstTaxClass ){
                            $newValue = ($option['value']);
                            $product->setData(self::MAGENTO_TAX_ATTRIBUTE_CODE, $newValue);
                            $product->getResource()->saveAttribute($product, self::MAGENTO_TAX_ATTRIBUTE_CODE, $storeId);
                        }
                    }       
                }
            }      
        }
 }