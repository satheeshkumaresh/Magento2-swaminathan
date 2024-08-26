<?php
namespace Swaminathan\Wishlist\Model;
use Swaminathan\CmsPlpPdp\Model\CmsPlpPdp ;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Wishlist\Model\ResourceModel\Item as ItemResource;
use Swaminathan\Wishlist\Api\GetWishlistInterface;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Wishlist\Model\Wishlist as wishlist;
use Magento\Quote\Model\Quote\ItemFactory  as ItemFactory;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory as wishlistcollection;
/**
 * Class WishlistRepository
 * @package Data\Add\Model
 */
class GetWishlist implements GetWishlistInterface
{

    const PAGE_LIMIT = 15;

    /**
     * @var Http
     */
    private $http;

    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ItemResource
     */
    private $itemResource;

    /**
     * @var CmsPlpPdp
     */
    private $cmsPlpPdp;

    /**
     * @var CustomerSession
     */
    private $customerSession;
    /**
     * WishlistRepository constructor.
     * @param Http $http
     * @param TokenFactory $tokenFactory
     * @param WishlistFactory $wishlistFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ItemResource $itemResource
     * @param WishlistCollectionFactory $wishlistCollectionFactory
     * @param CollectionFactory $CollectionFactory
     * @param wishlist $wishlist
     * @param CmsPlpPdp $cmsPlpPdp
     */
    public function __construct(
        Http $http,
        TokenFactory $tokenFactory,
        WishlistFactory $wishlistFactory,
        ProductRepositoryInterface $productRepository,
        ItemResource $itemResource,
        WishlistCollectionFactory $wishlistCollectionFactory,
        CollectionFactory $collectionFactory,
        wishlist $wishlist,
        ItemFactory $itemFactory,
        ProductFactory $productFactory,
        wishlistcollection $wishlistcollection,
        ProductHelper $productHelper,
        StockRegistryInterface $StockRegistryInterface,
        CmsPlpPdp $cmsPlpPdp,
    ) {
        $this->http = $http;
        $this->tokenFactory = $tokenFactory;
        $this->wishlistFactory = $wishlistFactory;
        $this->productRepository = $productRepository;
        $this->itemResource = $itemResource;
        $this->wishlistCollectionFactory = $wishlistCollectionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->wishlist = $wishlist;
        $this->itemFactory = $itemFactory;
        $this->wishlistcollection = $wishlistcollection;
        $this->productFactory = $productFactory;
        $this->productHelper = $productHelper;
        $this->stockRegistryInterface = $StockRegistryInterface;
        $this->cmsPlpPdp = $cmsPlpPdp;
    }
     /**
     * @param mixed $pageSize
     * @param int $currPage
     * @inheritdoc
     * @return array
     */
    public function getCurrentWishlist($pageSize,$currPage)   
    {
        $authorizationHeader = $this->http->getHeader('Authorization');
        $tokenParts = explode('Bearer', $authorizationHeader);
        $tokenPayload = trim(array_pop($tokenParts));
        /** @var Token $token */
        $token = $this->tokenFactory->create();
        $token->loadByToken($tokenPayload);
        $customerId = $token->getCustomerId();
        /** @var Wishlist $wishlist */
        $count=[];
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId);
            if($wishlist) {
                $totalCount = count($wishlist->getItemCollection()->getData());                    
            } 
        $wishlistCollection = $this->wishlistcollection->create();
        $wishlistCollection->addFieldToFilter('customer_id', $customerId);
        $wishlist = $wishlistCollection->getFirstItem();
        if($pageSize == 0 || $pageSize == ""){
                $getWishlistData = $wishlist->getItemCollection(); 
                $getWishlistData->setPageSize(self::PAGE_LIMIT);
                $getWishlistData->setCurPage($currPage);  
        }
        else{
            $getWishlistData = $wishlist->getItemCollection(); 
            $getWishlistData->setPageSize($pageSize);
            $getWishlistData->setCurPage($currPage);  
        }
        $data = [];
        $specialPrice = "";
        foreach ($getWishlistData as $item) { 
            $wishlistData = [];
            $wishlistData['wishlist_item_id'] = $item->getwishlistItemId();
            $wishlistData['wishlist_id'] = $item->getwishlistId(); 
            $wishlistData['product_id'] = $item->getproduct()->getEntityId(); 
            $wishlistData['sku'] = $item->getproduct()->getSku(); 
            $productType= $item->getproduct()->getTypeId(); 
            $wishlistData['type_id'] = $productType;
            $wishlistData['name'] = $item->getproduct()->getName();                        
            $wishlistData['qty'] = round($item->getQty(), 0);
            $price = $item->getPrice();
            $productId = $item->getproduct()->getEntityId(); 
            $wishlistData['price'] = "";
            $wishlistData['display_price'] = "";
            $wishlistData['special_price'] = "";
            $wishlistData['display_special_price'] = "";
            $OfferArrivalTag['on_offer'] = "";
            $OfferArrivalTag['new_arrival'] = "";
            $wishlistData['starting_from_price'] = "";
            $wishlistData['starting_from_display_price'] = "";
            $wishlistData['starting_to_price'] = "";
            $wishlistData['starting_to_display_price'] = "";
            $wishlistData['children_count'] = "";
            $productDataFactory =  $this->productFactory->create()->load($productId);
            if($productType == "simple"){
                if($price != ""){
                    $wishlistData['price'] = $this->productHelper->getFormattedPrice($price);
                    $wishlistData['display_price'] = $this->productHelper->INDMoneyFormat($price);
                }
                $specialPriceData=$this->cmsPlpPdp->getSpecialPriceByProductId($productId);
                if(!empty($specialPriceData['special_price'])){
                    $wishlistData['special_price'] = $this->productHelper->getFormattedPrice($specialPriceData['special_price']);
                    $wishlistData['display_special_price'] = $this->productHelper->INDMoneyFormat($specialPriceData['special_price']);
                }
                $OfferArrivalTag['on_offer'] = $specialPriceData['tag'];
            }
            else if($productType == "grouped"){
                $groupedProductDatas = $this->cmsPlpPdp->getGroupedProductPrice($productDataFactory);
                $wishlistData['starting_from_price'] = $groupedProductDatas['starting_from_price'];
                $wishlistData['starting_from_display_price'] = $groupedProductDatas['starting_from_display_price'];
                $wishlistData['starting_to_price'] = $groupedProductDatas['starting_to_price'];
                $wishlistData['starting_to_display_price'] = $groupedProductDatas['starting_to_display_price'];
                $wishlistData['tags'] = $groupedProductDatas['tags'];
                $wishlistData['children_count'] = $groupedProductDatas['children_count'];
            }
            // Get new arrival and offer tags
            $arrival = $this->cmsPlpPdp->getNewFromToDate($productId);
            $OfferArrivalTag['new_arrival'] = $arrival['tag'];
            $wishlistData['tags'] = $OfferArrivalTag;      
            $productData = $this->productFactory->create()->load($productId);
            $wishlistData['color'] = $this->productHelper->getColorAttributeValue($productData);
            $wishlistData['weight_in_kg'] = $this->productHelper->getSizeAttributeValue($productData);
            $wishlistData['product_url'] = $this->productHelper->getProductRewriteUrl($productId);
            $wishlistData['image'] = $this->productHelper->getProductImage($productId);
            $productStock = $this->stockRegistryInterface->getStockItem($productId);
            $stockStatus = $productStock->getIsInStock();
            if($stockStatus > 0){
                $wishlistData['stock'] = "Instock";
            }
            else{
                $wishlistData['stock'] = "OutOfstock";
            }
            $data[] = $wishlistData;            
        }
        $response[] =[
            "code" => 200,
            "status" => true,
            "item_count"=> $totalCount,
            "show_per" =>$pageSize,
            "page" => $currPage,
            "data" => $data,
        ];       
        return $response;    
    }
}