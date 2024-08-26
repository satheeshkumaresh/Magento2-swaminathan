<?php
namespace Swaminathan\Cart\Helper;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Magento\Catalog\Model\ProductFactory;
use Swaminathan\CmsPlpPdp\Model\CmsPlpPdp;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\Quote\Item\OptionFactory as OptionFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class Data
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var OptionFactory
     */
    protected $optionFactory;

     /**
     * @var ProductHelper
     */
    protected $productHelper;

    protected $getSalableQuantityDataBySku;

    public function __construct(
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        ProductHelper $productHelper,
        CmsPlpPdp $cmsPlpPdp,
        StockRegistryInterface $StockRegistryInterface,
        OptionFactory $optionFactory,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        Product $product, 
        CollectionFactory $categoryCollection
    )
    {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->productHelper = $productHelper;
        $this->cmsPlpPdp = $cmsPlpPdp;
        $this->stockRegistryInterface = $StockRegistryInterface;
        $this->optionFactory = $optionFactory;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->product = $product;
        $this->categoryCollection = $categoryCollection;
    }
    public function getQuoteItem($item){
        $qty = 0;
        $groupedProductId = "";
        $itemData = $item->getData();
        $options = $this->optionFactory->create()->getCollection();
        $options->addFieldToFilter('item_id', $itemData['item_id']);
        foreach($options->getData() as $option){
            if($option['code'] == "product_type" && $option['value'] == "grouped"){
                $groupedProductId = $option['product_id'];
            }
        }
        $productId = $itemData['product_id'];
        $productdata = [];
        $productdata['item_id'] = $itemData['item_id'];
        $productdata['quote_id'] = $itemData['quote_id'];
        $productdata['sku'] = $itemData['sku'];
        $productdata['name'] = $itemData['name'];
        $productdata['qty'] = $itemData['qty'];
        $productdata ['category_name']= $this->getCategoriesName($productId);
        $productId = $item->getProductId();
        $arrival = $this->cmsPlpPdp->getNewFromToDate($productId);
        $productDetails = $this->productFactory->create()->load($productId);
        $productPrice=$productDetails->getPrice();
        $productdata['special_price'] = "";
        $productdata['display_special_price'] = "";
        $offer['tag'] = "";
        if($productPrice != $itemData['price']){
            $offer = $this->cmsPlpPdp->getSpecialPriceByProductId($productId);
            $productdata['price'] = $this->productHelper->getFormattedPrice($productPrice);
            $productdata['display_price'] = $this->productHelper->INDMoneyFormat($productPrice);
            if(!empty($offer['special_price'])){
                $productdata['special_price'] = $this->productHelper->getFormattedPrice($offer['special_price']);
                $productdata['display_special_price'] = $this->productHelper->INDMoneyFormat($offer['special_price']);
            }
        }
        else{
            $productdata['price'] = $this->productHelper->getFormattedPrice($itemData['price']);
            $productdata['display_price'] = $this->productHelper->INDMoneyFormat($itemData['price']);
        }
        $productdata['row_total'] = $this->productHelper->getFormattedPrice($itemData['row_total']);
        $productdata['display_row_total'] = $this->productHelper->INDMoneyFormat($itemData['row_total']);
        $productdata['offer_tag'] = $offer['tag'];
        $productdata['arrival_tag' ] = $arrival['tag'];
        $productData = $this->productFactory->create()->load($productId);
        $productdata['color'] = $this->productHelper->getColorAttributeValue($productData);
        $productdata['weight_in_kg'] = $this->productHelper->getSizeAttributeValue($productData);
        if($groupedProductId != ""){
            $productdata['product_url'] = $this->productHelper->getProductRewriteUrl($groupedProductId);
        }
        else{
            $productdata['product_url'] = $this->productHelper->getProductRewriteUrl($productId);
        }
        $productdata['image'] = $this->productHelper->getProductImage($productId); 
        $productStock = $this->stockRegistryInterface->getStockItem($productId);
        $stockStatus = $productStock->getIsInStock();
        $stockState = $this->getSalableQuantityDataBySku;
        $qty = $stockState->execute($itemData['sku']);
        $produtQty = $qty[0]['qty'];
        if($itemData['qty'] > $produtQty){
            $productdata['stock'] = "Out of Stock";
        }
        else{
            $productdata['stock'] = "In Stock";
        }   
        $productdata['available_qty'] = $produtQty;   
        return $productdata;
    }
    public function getCrossSellProducts($productId){
        $crossSellProduct = [];
        $data = [];
        $product = $this->productRepository->getById($productId);
        $crossSell = $product->getCrossSellProducts();
        if (count($crossSell)) {
            foreach ($crossSell as $productItem) {
                $productDataFactory = $this->productFactory->create()->load($productItem->getId());
                $productData = $productDataFactory->getData();
                if(($productData['visibility'] != CmsPlpPdp::VISIBILITY) && ($productData['status'] == CmsPlpPdp::ENABLED)){
                    $data['product_id'] = $productData['entity_id'];
                    $data['name'] = $productData['name'];
                    $data['sku'] = $productData['sku'];
                    $data['type'] = $productData['type_id'];
                    $data['product_url'] = $this->productHelper->getProductRewriteUrl($productData['entity_id']);
                    $data['image'] = $this->productHelper->getProductImage($productData['entity_id']); 
                    $tag = [];
                    $newArrivalOnOfferTags = [];
                    $newArrival = $this->cmsPlpPdp->getNewFromToDate($productData['entity_id']);
                    $tag['new_arrival'] = $newArrival['tag'];
                    $tag['on_offer'] = "";
                    $data['price'] = "";
                    $data['display_price'] = "";
                    $data['special_price'] = "";
                    $data['display_special_price'] = "";
                    $data['starting_from_price'] = "";
                    $data['display_starting_from_price'] = "";
                    $data['starting_to_price'] = "";
                    $data['display_starting_to_price'] = "";
                    if($productData['type_id'] == 'simple'){
                        $specialPriceByProductId = $this->cmsPlpPdp->getSpecialPriceByProductId($product['entity_id']);
                        $tag['on_offer'] = $specialPriceByProductId['tag'];
                        if($specialPriceByProductId['special_price'] != ""){
                            $data['special_price'] = $this->productHelper->getFormattedPrice($specialPriceByProductId['special_price']);
                            $data['display_special_price'] = $this->productHelper->INDMoneyFormat($specialPriceByProductId['special_price']);
                        }
                        $data['price'] =  $this->productHelper->getFormattedPrice($product['price']);
                        $data['display_price'] =  $this->productHelper->INDMoneyFormat($product['price']);                                  
                    } 
                    else if($productData['type_id'] == 'grouped'){
                        $groupedProducts = $this->cmsPlpPdp->getGroupedProductPrice($productDataFactory);
                        $data['starting_from_price']  = $groupedProducts['starting_from_price'];
                        $data['display_starting_from_price'] = $groupedProducts['starting_from_display_price'];
                        $data['starting_to_price'] = $groupedProducts['starting_to_price'];
                        $data['display_starting_to_price'] = $groupedProducts['starting_to_display_price'];
                        $tag['new_arrival'] = $groupedProducts['tags']['new_arrival'];
                        $tag['on_offer'] = $groupedProducts['tags']['on_offer'];                        
                    }
                    $newArrivalOnOfferTags[] = $tag;
                    $data['tags'] = $tag;
                    $crossSellProduct[] = $data;
                }
            }
        }
        return $crossSellProduct;
    }
        public function getCategoriesName($productId)
    {
        $product = $this->productFactory->create()->load($productId);
       
        $categoryIds = $product->getCategoryIds();
        $categories = $this->categoryCollection->create()->addAttributeToSelect('*')->addAttributeToFilter('entity_id', $categoryIds);
        $categoryNames = [];
        foreach ($categories as $category) {
            $categoryNames[] = $category->getName();
        }
        $categoryName = implode(',', $categoryNames);
        return $categoryName;
    }
}
