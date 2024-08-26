<?php
namespace Swaminathan\CmsPlpPdp\Helper;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use Magento\Directory\Model\Currency;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Magento\Framework\Pricing\Helper\Data as Pricehelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data
{
    const COLOR_ATTRIBUTE_CODE = "color";

    const SIZE_IN_KG_ATTRIBUTE_CODE = "size_in_kg";

    const MATERIAL_ATTRIBUTE_CODE = "material";

    protected $urlFinderInterface;
    protected $urlHelper;
    protected $pricehelper;
    protected $currency;
    protected $priceCurrencyInterface;
    protected $productRepositoryInterfaceFactory;
     /**
    * @var StoreManagerInterface
    */
    private $storeManager;
    /**
        * @var StockResolverInterface
        */
    private $stockResolver;
    /**
        * @var GetProductSalableQtyInterface
        */
    private $getProductSalableQty;

    /**
        * @var array
        */
    private $stockIdCache=[];
    public function __construct(
        UrlFinderInterface $urlFinderInterface,
        UrlHelper $urlHelper,
        Currency $currency,
        Pricehelper $pricehelper,
        ProductRepositoryInterfaceFactory $productRepositoryInterfaceFactory,
        PriceCurrencyInterface $priceCurrencyInterface,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        GetProductSalableQtyInterface $getProductSalableQty    )
    {
        $this->urlFinderInterface = $urlFinderInterface;  
        $this->urlHelper = $urlHelper;
        $this->currency = $currency;
        $this->pricehelper = $pricehelper;
        $this->productRepositoryInterfaceFactory = $productRepositoryInterfaceFactory;
        $this->priceCurrencyInterface = $priceCurrencyInterface;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->getProductSalableQty = $getProductSalableQty;    }
    // get product rewrite url 
    public function getProductRewriteUrl($productId){
        $rewriteUrl = "";
        $productRewriteUrl = [
            'entity_id' => $productId,
            'entity_type' => 'product',
            'redirect_type' => 0

        ];
        $rewrite = $this->urlFinderInterface->findOneByData($productRewriteUrl);
        if($rewrite != Null){
            $rewriteUrl = $rewrite->getRequestPath();
        }
        return $rewriteUrl;
    }
    // get product  url 
    public function getProductUrl($productId){
        $productRewriteUrl = [
            'entity_id' => $productId,
            'entity_type' => 'product',
            'redirect_type' => 0
        ];
        $rewrite = $this->urlFinderInterface->findOneByData($productRewriteUrl);
        $reactUrl = $this->urlHelper->getReactUrl();
        $productUrl = $reactUrl . $rewrite->getRequestPath();
        return $productUrl;
    }
    // get category rewrite url 
    public function getCategoryRewriteUrl($categoryId){
        $categoryUrl = "";
        if(!empty($categoryId)){
            $productRewriteUrl = [
                'entity_id' => $categoryId,
                'entity_type' => 'category',
                'redirect_type' => 0
            ];
            $rewrite = $this->urlFinderInterface->findOneByData($productRewriteUrl);
            $categoryUrl = $rewrite->getRequestPath();
        }
        return $categoryUrl;
    }
    // Get Formatted Show Price without currency
    public function getFormattedShowdPrice($price)
    {
        $currency = $this->currency;
        return $currency->format($price, ['display'=>\Zend_Currency::NO_SYMBOL], false);
    }
    // Get Product Color Atrribute Value
    public function getColorAttributeValue($productData){
        $productColor = "";
        if($productData->getResource()->getAttribute(self::COLOR_ATTRIBUTE_CODE)->getFrontend()->getValue($productData) != false){
            $productColor = $productData->getResource()->getAttribute(self::COLOR_ATTRIBUTE_CODE)->getFrontend()->getValue($productData);
        }
        return $productColor;
    }
     // Get Product Weight In Kg Atrribute Value
     public function getSizeAttributeValue($productData){
        $weight = "";
        if($productData->getResource()->getAttribute(self::SIZE_IN_KG_ATTRIBUTE_CODE)->getFrontend()->getValue($productData) != false){
            $weight = $productData->getResource()->getAttribute(self::SIZE_IN_KG_ATTRIBUTE_CODE)->getFrontend()->getValue($productData);
        }
        return $weight;
    }
      // Get Product Mateial Atrribute Value
      public function getMaterialAttributeValue($productData){
        $material = "";
        if($productData->getResource()->getAttribute(self::MATERIAL_ATTRIBUTE_CODE)->getFrontend()->getValue($productData) != false){
            $material = $productData->getResource()->getAttribute(self::MATERIAL_ATTRIBUTE_CODE)->getFrontend()->getValue($productData);
        }
        return $material;
    }
    // Get product image by product id
    public function getProductImage($productId){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $product = $this->productRepositoryInterfaceFactory->create()->getById($productId);
        if(isset($product['main_image_s3_url']) && $product['main_image_s3_url'] != null && $product['main_image_s3_url'] != ""){
            $productImage = $product['main_image_s3_url'];
        }
        else{
            $productImage = $this->urlHelper->getPlaceHolderImage();
        }
        return $productImage;
    }
    // get price with currency 
    public function getPriceCurrency($price){
        return $this->pricehelper->currency($price, true, false);
    }
    // get Min and Max price 
    public function getPriceFilterFormat($price){
        return $this->currency->format($price, ['display'=>\Zend_Currency::NO_SYMBOL], false);
    }
     // Get Formatted Price without currency
     public function getFormattedPrice($price)
     {
        $result = "";
        if(!empty($price)){
            $result = $this->priceCurrencyInterface->roundPrice($price);
        }
        return $result;
     }
     // INR Money Format
     function INDMoneyFormat($price){
        $result = "";
        if(!empty($price)){
            $decimalPoint = ".00";
            $decimal = (string)($price - floor($price));
            $money = floor($price);
            $length = strlen($money);
            $delimiter = '';
            $money = strrev($money);

            for($i=0;$i<$length;$i++){
                if(( $i==3 || ($i>3 && ($i-1)%2==0) )&& $i!=$length){
                    $delimiter .=',';
                }
                $delimiter .=$money[$i];
            }

            $result = strrev($delimiter);
            $decimal = preg_replace("/0\./i", ".", $decimal);
            $decimal = substr($decimal, 0, 3);

            if( $decimal != '0'){
                $result = $result.$decimal;
            }
            else{
                $result = $result.$decimalPoint;
            }
        }
        return $result;
     }
    // get salable quantity by product sku
    public function getSalableQuantity($productSku){

        $salableQty=0;
        try {
            $store = $this->storeManager->getStore();
            $stockId = $this->getStockId($store);
  
            $salableQty = $this->getProductSalableQty->execute($productSku, $stockId);
        }catch (\Exception $exception){
            $salableQty=0;
        }
  
        return $salableQty;
    }
  
    private function getStockId($store){
  
        $websiteCode = $store->getWebsite()->getCode();
  
        if(!isset($this->stockIdCache[$websiteCode])) {
  
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
            $stockId = (int)$stock->getStockId();
  
            $this->stockIdCache[$websiteCode] = $stockId;
        }
  
        return $this->stockIdCache[$websiteCode];
    }
}
