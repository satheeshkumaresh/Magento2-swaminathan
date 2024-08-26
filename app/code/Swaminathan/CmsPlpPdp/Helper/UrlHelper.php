<?php

namespace Swaminathan\CmsPlpPdp\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;



class UrlHelper extends AbstractHelper
{
    const REACT_URL = "web/general/react_url";
    const BASE_URL = "admin/url/custom";
    const PLACE_HOLDER_IMAGE = 'catalog/placeholder/image_placeholder';
    const ITEM_PER_PAGE = 'plp_pagination/pagination_count/count_value';
    const FROM_SAME_CATEGORY_LIMIT = 'plp_pagination/from_same_category/count_value';
    const CORSS_SELL_PRODUCT_LIMIT = 'plp_pagination/cross_sell_product/count_value';

    public function __construct( 
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfigInterface
    ) { 
        $this->storeManager = $storeManager;
        $this->scopeConfigInterface = $scopeConfigInterface;

    }
    public function getBaseUrl()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        return $this->scopeConfigInterface->getValue(self::BASE_URL, $storeScope);
    }

    public function getReactUrl()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        return $this->scopeConfigInterface->getValue(self::REACT_URL, $storeScope);
    }

    public function getMediaUrl()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        return $this->scopeConfigInterface->getValue(self::BASE_URL, $storeScope) . "media/" ;
         
    }

    public function getPlaceHolderImage(){
        $configPath = $this->storeManager->getStore()->getConfig(self::PLACE_HOLDER_IMAGE);
        if($configPath){
            return $this->getMediaUrl().'catalog/product/placeholder/'.$configPath;
        }
        else{
            return '';
        }
    }

    public function getProductPerPage()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        return $this->scopeConfigInterface->getValue(self::ITEM_PER_PAGE, $storeScope);
    }

    public function getCrossSellProductLimit()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        return $this->scopeConfigInterface->getValue(self::CORSS_SELL_PRODUCT_LIMIT, $storeScope);
    }

    public function getSameCategoryLimit()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
        return $this->scopeConfigInterface->getValue(self::FROM_SAME_CATEGORY_LIMIT, $storeScope);
    }

}