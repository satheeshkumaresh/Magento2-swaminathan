<?php
namespace Swaminathan\CmsPlpPdp\Model;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Search\Model\QueryFactory;
use Magento\CatalogSearch\Helper\Data as HelperData;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use Magento\Catalog\Model\CategoryFactory;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Swaminathan\CmsPlpPdp\Model\Sort;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProduct;
use Magento\Catalog\Model\ProductRepository;
use Swaminathan\CmsPlpPdp\Model\CmsPlpPdp;
use Swaminathan\HomePage\Model\HomePage;

class Search implements \Swaminathan\CmsPlpPdp\Api\SearchInterface
{

    const PAGESIZE = 10;

    protected $timezoneInterface;

    protected $storeManager;

    protected $queryFactory;

    protected $helperData;

    protected $urlHelper;

    protected $categoryModelFactory;

    protected $productHelper;

    protected $productCollectionFactory;

    protected $sort;

    protected $groupedProduct;

    protected $productRepository;

    protected $cmsPlpPdp;

    public function __construct(
        TimezoneInterface $timezoneInterface,
        StoreManagerInterface $storeManager,
        QueryFactory $queryFactory,
        HelperData $helperData,
        UrlHelper $urlHelper,
        CategoryFactory $categoryModelFactory,
        ProductHelper $productHelper,
        CollectionFactory $productCollectionFactory,
        Sort $sort,
        GroupedProduct $groupedProduct,
        ProductRepository $productRepository,
        CmsPlpPdp $cmsPlpPdp,
        HomePage $homePage
    ) {
        $this->timezoneInterface = $timezoneInterface;
        $this->storeManager = $storeManager;
        $this->queryFactory = $queryFactory;
        $this->helperData = $helperData;
        $this->urlHelper = $urlHelper;
        $this->categoryModelFactory = $categoryModelFactory;
        $this->productHelper = $productHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->sort = $sort;
        $this->groupedProduct = $groupedProduct;
        $this->productRepository = $productRepository;
        $this->cmsPlpPdp = $cmsPlpPdp;
        $this->homePage =$homePage;
    }

/**
     * Returns a list of the searched suggestion.
     */
    public function getSearchSuggestion($data){
        // Get current date & time
        $updatedDate = $this->timezoneInterface
                                            ->date()
                                            ->format('Y-m-d H:i:s');
        // Get website Id 
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        // Get store Id 
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        // Get Media url
        $mediaUrl = $this->urlHelper->getMediaUrl();
        // Get today date
        $todayDate = $this->timezoneInterface
                                            ->date()
                                            ->format('Y-m-d');
        if(isset($data['keyword']) && $data['keyword'] != ""){
            // get product collection
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect('*');
            // Filter enable product
            $productCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); 
            // Filter Product Visibility
            $productCollection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)); 
            // Filter product name / sku / description
            $searchTerm = trim($data['keyword']);
            $searchTerms = explode(' ', $searchTerm);
            $productCollection->addAttributeToFilter(
                [
                    ['attribute' => 'name',
                     'regexp' => implode('|', array_map(function($term) {
                        return '(^|\s)' . preg_quote($term) . '(\s|$)';
                     }, $searchTerms))],
                    ['attribute' => 'sku', 'like' => '%' . $searchTerm . '%'],
                    ['attribute' => 'name', 'like' => '%' . $searchTerm . '%'],
                    ['attribute' => 'description', 'null' => true, 'like' => '%' . $searchTerm . '%'],
                ]
            )->addAttributeToFilter('sku', ['like' => '%' . $searchTerm . '%']);
            $productCollection->addAttributeToFilter(
                [
                    ['attribute'=>'name','nlike'=>'%'.$searchTerm.'%'],
                ]
            );
            $relevanceSearchCount = 0;
            $relevanceSearch = [];
            $relevanceSearch = $this->getRelevanceSearchproducts(trim($data['keyword']), "", "", "");
            $getGroupedProductSearchRelevance = $this->getGroupedProductSearchRelevance(trim($data['keyword']), "", "", "");
            $relevanceSearchCount = count($relevanceSearch) + count($getGroupedProductSearchRelevance);
            $itemCount = count($productCollection) + $relevanceSearchCount ;
            $productCollection->setPageSize(self::PAGESIZE);
            $itemDatas = array_merge($relevanceSearch, $productCollection->getData());
            $itemData = array_merge($itemDatas,$getGroupedProductSearchRelevance);
            $responsedatas['meta_title'] = 'search result for '.trim($data['keyword']);
            $responsedatas['meta_keyword']= $this->homePage->getMetaKeywords();
            $responsedatas['meta_description']= $this->homePage->getMetaDescription();
            $searchProductDetails = [];
            $productInfo = [];
            $data = [];
            if(!empty($itemData) || (!empty($getGroupedProductSearchRelevance))){
                foreach($itemData as $productDatas){
                    $productInfo[] = $this->getsearchSuggestionProducts($productDatas['entity_id']);
                }
               //searchProductInfo = array_merge($productInfo, $getGroupedProductSearchRelevance);
               $searchCollectionData = array_map("unserialize", array_unique(array_map("serialize", $productInfo)));
               $searchProductInfo = array_values($searchCollectionData);
               $itemCount = count($searchProductInfo);
                
              $responsedatas['item_count'] = $itemCount;        
              $responsedatas['products'] = $searchProductInfo;
                $responseData = [
                    "code" => 200,
                    "status" => true,
                    "data" => $responsedatas
                ];
            }
            else{
                $responsedatas['products'] = $productInfo;
                $responseData = [
                    "code" => 200,
                    "status" => true,
                    "message" => "No products found. Try searching something else.",
                    "data" => $responsedatas                    ];
            }
        }
        else{
            $responseData = [
                "code" => 400,
                "status" => false,
                "message" => "Parameter Missing"
            ];
        }
        $response[]  = $responseData;
        return $response;
    }
    // relevance sort by search keyword
    public function getRelevanceSearchproducts($searchTerm, $minPrice, $maxPrice, $filterableCategoryId){
        if(!empty($filterableCategoryId)){
            $categories = $this->categoryModelFactory->create()->load($filterableCategoryId);
            $productCollection = $categories->getProductCollection();
            $productCollection->addAttributeToSelect('name');
            $productCollection->addAttributeToSelect('status');
            $productCollection->addAttributeToSelect('visibility');
            // Filter enable product
            $productCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
            // Filter Product Visibility
            $productCollection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE));
            $productCollection->addAttributeToFilter(
                [
                    ['attribute'=>'name','like'=>'%'.$searchTerm.'%'],
                    ['attribute'=>'sku','like'=>'%'.$searchTerm.'%'],
                ]
            );
            if($maxPrice != '' && $maxPrice != 0 && $minPrice != ''){
                $productCollection->addPriceDataFieldFilter('%s >= %s', ['max_price', $minPrice]);
                $productCollection->addPriceDataFieldFilter('%s <= %s', ['min_price', $maxPrice]);
                $productCollection->addMinimalPrice();
            }
            else{
                $productCollection->addMinimalPrice();
            }
            $products = $productCollection->getData();
        }
        else{
            // get product collection
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect('name');
            $productCollection->addAttributeToSelect('status');
            $productCollection->addAttributeToSelect('visibility');
            // Filter enable product
            $productCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
            // Filter Product Visibility
            $productCollection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE));
            $productCollection->addAttributeToFilter(
                [
                    ['attribute'=>'name','like'=>'%'.$searchTerm.'%'],
                    ['attribute'=>'sku','like'=>'%'.$searchTerm.'%'],
                ]
            );
            if($maxPrice != '' && $maxPrice != 0 && $minPrice != ''){
                $productCollection->addPriceDataFieldFilter('%s >= %s', ['max_price', $minPrice]);
                $productCollection->addPriceDataFieldFilter('%s <= %s', ['min_price', $maxPrice]);
                $productCollection->addMinimalPrice();
            }
            else{
                $productCollection->addMinimalPrice();
            }
            $products = $productCollection->getData();
        }
        return $products;
    }
    public function getGroupedProductSearchRelevance($searchTerm, $minPrice, $maxPrice, $filterableCategoryId){
        if(!empty($filterableCategoryId)){
            $categories = $this->categoryModelFactory->create()->load($filterableCategoryId);
            $productCollection = $categories->getProductCollection();
            $productCollection->addAttributeToSelect('name');
            $productCollection->addAttributeToSelect('status');
            $productCollection->addAttributeToSelect('visibility');
            // Filter enable product
            $productCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
            // Filter Product Visibility
            $productCollection->addAttributeToFilter('visibility',array('eq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE));
            $productCollection->addAttributeToFilter(
                [
                    ['attribute'=>'name','like'=>'%'.$searchTerm.'%'],
                    ['attribute'=>'sku','like'=>'%'.$searchTerm.'%'],
                ]
            );
             foreach ($productCollection as $productData){
                $productsinfo = $this->groupedProduct->getParentIdsByChild($productData['entity_id']);
            }
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addFieldToFilter('entity_id',$productsinfo);
              if($maxPrice != '' && $maxPrice != 0 && $minPrice != ''){
                $productCollection->addPriceDataFieldFilter('%s >= %s', ['max_price', $minPrice]);
                $productCollection->addPriceDataFieldFilter('%s <= %s', ['min_price', $maxPrice]);
                $productCollection->addMinimalPrice();
            }
            else{
                $productCollection->addMinimalPrice();
            } 
             $searchSuggestion = $productCollection->getData();
        }
        else{ 
            // get product collection
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToSelect('name');
            $productCollection->addAttributeToSelect('status');
            $productCollection->addAttributeToSelect('visibility');
            // Filter enable product
            $productCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
            // Filter Product Visibility
            $productCollection->addAttributeToFilter('visibility',array('eq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE));
            $productCollection->addAttributeToFilter(
                [
                    ['attribute'=>'name','like'=>'%'.$searchTerm.'%'],
                    ['attribute'=>'sku','like'=>'%'.$searchTerm.'%'],
                    
                    
                ]
            );
            $groupedProductTotalCount = count($productCollection->getData());
            $productsinfo =[];
            $searchSuggestion =[];
             if($groupedProductTotalCount != 0){
                foreach ($productCollection as $productData){
                    $productsinfo = $this->groupedProduct->getParentIdsByChild($productData['entity_id']);
                }
                $productCollection = $this->productCollectionFactory->create();
                $productCollection->addFieldToFilter('entity_id',$productsinfo);
                if($maxPrice != '' && $maxPrice != 0 && $minPrice != ''){
                    $productCollection->addPriceDataFieldFilter('%s >= %s', ['max_price', $minPrice]);
                    $productCollection->addPriceDataFieldFilter('%s <= %s', ['min_price', $maxPrice]);
                    $productCollection->addMinimalPrice();
                }
                else{
                    $productCollection->addMinimalPrice();
                } 
           $searchSuggestion = $productCollection->getData();
           }
        }
        return $searchSuggestion;
    }
    public function getsearchSuggestionProducts($productId)
    {
        // get media url
        $mediaUrl = $this->urlHelper->getMediaUrl();
        // get today date
        $todayDate = $this->timezoneInterface
                                            ->date()
                                            ->format('Y-m-d');
        $product = $this->productRepository->getById($productId);
        // On offer / New Arrival tag
        $newDate = $this->cmsPlpPdp->getNewFromToDate($product['entity_id']);
        $newPrice = $this->cmsPlpPdp->getSpecialPriceByProductId($product['entity_id']);
        $tag['new_arrival'] = "";
        $tag['on_offer'] = "";
        if($newDate['tag'] != ""){
            $tag['new_arrival'] = $newDate['tag']; 
        }
        if($newPrice['tag'] != ""){
            $tag['on_offer'] = $newPrice['tag']; 
        }
        $sizeInKg = "";
        if(!empty($this->productHelper->getSizeAttributeValue($product))){
            $sizeInKg = $this->productHelper->getSizeAttributeValue($product);
        }
        $productColor = "";
        if(!empty($this->productHelper->getColorAttributeValue($product))){
            $productColor = $this->productHelper->getColorAttributeValue($product);
        }
        // Product Price
        $productShowPrice = "";
        $showSpecialPrice = "";
        $showPrice = "";
        $productPrice = "";
        $specialPrice = "";
        $startingFromPrice = "";
        $startingFromDisplayPrice = "";
        $startingToPrice = "";
        $startingToDisplayPrice = "";
        $productType = $product['type_id'];
        if($productType == "grouped"){
            $groupedProducts = $this->cmsPlpPdp->getGroupedProductPrice($product);
            $startingFromPrice = $groupedProducts['starting_from_price'];
            $startingFromDisplayPrice = $groupedProducts['starting_from_display_price'];
            $startingToPrice = $groupedProducts['starting_to_price'];
            $startingToDisplayPrice = $groupedProducts['starting_to_display_price'];
            $tag['new_arrival'] = $groupedProducts['tags']['new_arrival'];
            $tag['on_offer'] = $groupedProducts['tags']['on_offer'];
            $childrenCount = $groupedProducts['children_count'];
        }
        else if($productType == "simple"){
            $specialPriceByProductId = $this->cmsPlpPdp->getSpecialPriceByProductId($product['entity_id']);
            if($specialPriceByProductId['special_price'] != ""){
                $specialPrice = $this->productHelper->getFormattedPrice($specialPriceByProductId['special_price']);
                $showSpecialPrice = $this->productHelper->INDMoneyFormat($specialPriceByProductId['special_price']);
            }
            $productPrice =  $this->productHelper->getFormattedPrice($product['price']);
            $productShowPrice =  $this->productHelper->INDMoneyFormat($product['price']);
        }
        $newArrivalOnOfferTags = [];
        if(!empty($tag)){
            $newArrivalOnOfferTags = $tag;
        }
        $searchProductDetails['product_id'] = $product->getId();
        $searchProductDetails['type_id'] = $product->getTypeId();
        $searchProductDetails['sku'] = $product->getSku();
        $searchProductDetails['name'] = $product->getName();
        $searchProductDetails['weight_in_kg'] = $sizeInKg;
        $searchProductDetails['color'] = $productColor;
        $searchProductDetails['price'] = $productPrice;
        $searchProductDetails['display_price'] = $productShowPrice;
        $searchProductDetails['special_price'] = $specialPrice;
        $searchProductDetails['display_special_price'] = $showSpecialPrice;
        $searchProductDetails['starting_from_price'] = $startingFromPrice;
        $searchProductDetails['starting_from_display_price'] = $startingFromDisplayPrice;
        $searchProductDetails['starting_to_price'] = $startingToPrice;
        $searchProductDetails['starting_to_display_price'] = $startingToDisplayPrice;
        if($product->getSpecialFromDate() != "" && $product->getSpecialToDate() != ""){
            $specialFromDate = date('Y-m-d', strtotime($product->getSpecialFromDate()));
            $specialToDate = date('Y-m-d', strtotime($product->getSpecialToDate()));
            if(($specialFromDate <= $todayDate) &&  ($specialToDate >= $todayDate)){
                $searchProductDetails['special_price'] = $specialPrice;
                $searchProductDetails['display_special_price'] = $showSpecialPrice;
            }
            else{
                $searchProductDetails['special_price'] = 0;
                $searchProductDetails['display_special_price'] = 0;
            }
        }
        $searchProductDetails['tags'] = $newArrivalOnOfferTags;
        $searchProductDetails['product_url'] = $this->productHelper->getProductRewriteUrl($product->getId());
        if(isset($product['main_image_s3_url']) && $product['main_image_s3_url'] != null && $product['main_image_s3_url'] != ""){
            $searchProductDetails['image'] = $product['main_image_s3_url'];
        }
        else{
            $searchProductDetails['image'] = $this->urlHelper->getPlaceHolderImage();
        }
        return $searchProductDetails;
    }
    /**
     * Returns a list of the searched results.
     */
    public function getSearchResult($data){
        // Get current date & time
        $updatedDate = $this->timezoneInterface
                            ->date()
                            ->format('Y-m-d H:i:s');
        $plpResponse = [];
        $categoryList = [];
        $tagFilter = "";
        $page = 1;
        if( isset($data['tag_filter']) && isset($data['min_price']) && isset($data['size']) && isset($data['show_page']) && isset($data['max_price']) && isset($data['sort_order']) && isset($data['page']) && isset($data['filterable_category_id']) && isset($data['keyword'])  && $data['keyword'] != "" ){
            $popularity = 1;
            // Store most searched keyword
            /* @var $query \Magento\Search\Model\Query */
            $searchItems = $this->queryFactory->create();
            $query = $this->queryFactory->get();
            $query->setStoreId($this->storeManager->getStore()->getId());
            if ($query->getQueryText() != '') {
                if ($this->helperData->isMinQueryLength()) {
                    $query->setId(0)->setIsActive(1)->setIsProcessed(1);
                } else {
                    $query->saveIncrementalPopularity();
                }
            } 
            $quertText = $this->queryFactory->create()
                ->getCollection()
                ->addFieldToFilter('query_text', trim($data['keyword']));
            $quertTextData = (!empty($quertText->getData())) ? $quertText->getData()[0]['query_id'] : "";
            $searchItemDatas = $this->queryFactory->create()->load($quertTextData);
            $searchItemData = $searchItemDatas->getData();
            if(!empty($searchItemData)){
                $popularity += $searchItemData['popularity'];
            }
            $tagFilter = $data['tag_filter'];
            $minPrice = $data['min_price'];
            $maxPrice = $data['max_price'];
            $sortOrder = $data['sort_order'];
            $page = $data['page'];
            $showPage = $data['show_page'];
            $size = $data['size'];
            $filterableCategoryId = $data['filterable_category_id'];
            // Get Website Id
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            // Get Store Id
            $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
            // Get Media Url
            $mediaUrl = $this->urlHelper->getMediaUrl();
            // get todya's date from timezoneinterface
            $todayDate = $this->timezoneInterface
                                                ->date()
                                                ->format('Y-m-d');            
            if(!empty($filterableCategoryId)){
                // get product collection from category 
                $categories = $this->categoryModelFactory->create()->load($filterableCategoryId);
                $productDataCollection = $categories->getProductCollection();
            }
            else{
                // get product collection
                $productDataCollection = $this->productCollectionFactory->create();
            }
            $productDataCollection->addAttributeToSelect('*'); 
            // Filter enabled products
            $productDataCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); // Filter enable product
            // Filter visibile products
            $productDataCollection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)); 
            // Filter sku / name / description
            $searchTerm = trim($data['keyword']);
            $searchTerms = explode(' ', $searchTerm);
            $productDataCollection->addAttributeToFilter(
                [
                    ['attribute' => 'name',
                     'regexp' => implode('|', array_map(function($term) {
                        return '(^|\s)' . preg_quote($term) . '(\s|$)';
                     }, $searchTerms))],
                    ['attribute' => 'sku', 'like' => '%' . $searchTerm . '%'],
                    ['attribute' => 'name', 'like' => '%' . $searchTerm . '%'],
                    ['attribute' => 'description', 'null' => true, 'like' => '%' . $searchTerm . '%'],
                ]
            )->addAttributeToFilter('sku', ['like' => '%' . $searchTerm . '%']);
            $relevanceSearchCount = 0;
            $relevanceSearch = [];
            if($data['sort_order'] == 0 || $data['sort_order'] == ""){
                $productDataCollection->addAttributeToFilter(
                    [
                        ['attribute'=>'name','nlike'=>'%'.$searchTerm.'%'],
                    ]
                );
                $getGroupedProductSearchRelevance = $this->getGroupedProductSearchRelevance($searchTerm, $minPrice, $maxPrice, $filterableCategoryId);
                $getRelevanceSearch = $this->getRelevanceSearchproducts($searchTerm, $minPrice, $maxPrice, $filterableCategoryId);
                //Remove Product Duplicate Value "getGroupedProductSearchRelevance vs getRelevanceSearch"
                $uniqueProductValues = [];
                foreach ($getRelevanceSearch as $row) {
                    $productDataCollect =  $getGroupedProductSearchRelevance;
                    if (!in_array($productDataCollect, $uniqueProductValues)) {
                        $uniqueProductValues = $productDataCollect;
                    }
                }
                $relevanceSearchDuplicateCount = count($uniqueProductValues);
                if($relevanceSearchDuplicateCount == 1){
                    $relevanceSearch = $getRelevanceSearch; 
                    $relevanceSearchCount = count($relevanceSearch);
                }else{
                    $relevanceSearch = array_merge($getRelevanceSearch,$getGroupedProductSearchRelevance);
                    $relevanceSearchCount = count($relevanceSearch);
                 }
            }
            if($maxPrice != '' && $maxPrice != 0 && $minPrice != ''){
                $productDataCollection->addPriceDataFieldFilter('%s >= %s', ['max_price', $minPrice]);
                $productDataCollection->addPriceDataFieldFilter('%s <= %s', ['min_price', $maxPrice]);
                $productDataCollection->addMinimalPrice();
            }
            else{
                $productDataCollection->addMinimalPrice();
            }
            // sort order functionality relevance, price low to high, price high to low, sort by name ASC and sort by name DESC
            switch ($sortOrder) {
                case Sort::RELEVENCE:
                    $productDataCollection->setOrder('entity_id', 'DESC');
                break;
                case Sort::PRICE_HIGH_TO_LOW:
                    $productDataCollection->setOrder('price', 'DESC');
                break;
                case Sort::PRICE_LOW_TO_HIGH:
                    $productDataCollection->setOrder('price', 'ASC');
                break;
                case Sort::NAME_ASC:
                    $productDataCollection->addAttributeToSort('name', 'ASC');
                break;
                case Sort::NAME_DESC:
                    $productDataCollection->addAttributeToSort('name', 'DESC');
                break;
                default:
                    $productDataCollection->setOrder('entity_id', 'DESC');
            }
            $filterableGroupedProducts = [];
            $filterableGroupedProductCount = 0;
            if(isset($size) && $size != 0 && $size != ""){
                if(!empty($filterableCategoryId)){
                    $categories = $this->categoryModelFactory->create()->load($filterableCategoryId);
                    $_productCollection = $categories->getProductCollection();
                }
                else{
                    // get product collection
                    $_productCollection = $this->productCollectionFactory->create();
                }
                $_productCollection->addAttributeToSelect('*'); 
                // status Filter
                $_productCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); 
                $_productCollection->addAttributeToFilter(
                    [
                        ['attribute'=>'size_in_kg','eq'=> $size] // size filter
                    ]
                );
                $parentIds = [];
                $childIds = [];
                foreach($_productCollection as $_product){
                    $childIds[]= $_product['entity_id'];
                }
                $parentId = $this->groupedProduct->getParentIdsByChild($childIds);
                $filterableGroupedProducts = $this->cmsPlpPdp->getFilterableGroupedProduct($parentId);
                $filterableGroupedProductCount = count($filterableGroupedProducts);
                $productDataCollection->addAttributeToFilter(
                    [
                        ['attribute'=>'size_in_kg','eq'=> $size] // size filter
                    ]
                );
            }
            $productDataCollections = (array) array_merge($relevanceSearch, $filterableGroupedProducts);
             $uniqueProductValue = [];
            foreach ($productDataCollections as $row) {
                $productDataCollect =  $productDataCollection->getData();
                if (!in_array($productDataCollect, $uniqueProductValue)) {
                    $uniqueProductValue = $productDataCollect;
                }
            }
            $count = count($uniqueProductValue);
            //Remove product duplicate value
            if($count == 1){
            $productDataCollections = (array) array_merge($relevanceSearch, $filterableGroupedProducts);
            $itemDatas ['product_count']  = $filterableGroupedProductCount + $relevanceSearchCount;
            }else{
            $productDataCollections = (array) array_merge($relevanceSearch, $filterableGroupedProducts,$productDataCollection->getData());
            $itemDatas ['product_count']  = count($productDataCollection) + $filterableGroupedProductCount + $relevanceSearchCount;
            }
            // Tag filter 
            $onOfferTagDatas = [];
            $newArrivalTagDatas = [];
            $filterOfferArrivalTag = $this->cmsPlpPdp->getTagFilter($productDataCollection, $tagFilter);
            if(!empty($filterOfferArrivalTag['on_offer'])){
                $onOffer['on_offer'] = $filterOfferArrivalTag['on_offer'];
                $onOfferTagDatas = $onOffer;
            }
            if(!empty($filterOfferArrivalTag['new_arrival'])){
                $newArrival['new_arrival'] = $filterOfferArrivalTag['new_arrival'];
                $newArrivalTagDatas = $newArrival;
            }
            $tagFilterSpecialPrice = $this->cmsPlpPdp->getTagPriceFilter(array_merge($relevanceSearch, $productDataCollection->getData()), $tagFilter);
            // pagination
            $itemPerPage = $this->urlHelper->getProductPerPage();
            $pageLimit = CmsPlpPdp::PAGE_LIMIT;
            $pagination = ($itemPerPage == "" || $itemPerPage == 0) ? $pageLimit :$itemPerPage;
            $productInCategory = count($productDataCollection) + $filterableGroupedProductCount + $relevanceSearchCount;
            $itemDatas ['searched_keyword'] = trim($data['keyword']);
           // $itemDatas ['product_count']  = $productInCategory;
            if(!empty($searchItemData)){
                $searchItemDatas->setData('query_text',trim($data['keyword']));
                $searchItemDatas->setData('num_results',$productInCategory);
                $searchItemDatas->setData('popularity',$popularity);
                $searchItemDatas->setData('store_id',$storeId);
                $searchItemDatas->setData('display_in_terms',1);
                $searchItemDatas->setData('is_active',1);
                $searchItemDatas->setData('is_processed',0);
                $searchItemDatas->setData('updated_at',$updatedDate);
                $searchItemDatas->save();
            }
            else{
                $searchItems->setData('query_text',trim($data['keyword']));
                $searchItems->setData('num_results',$productInCategory);
                $searchItems->setData('popularity',$popularity);
                $searchItems->setData('store_id',$storeId);
                $searchItems->setData('display_in_terms',1);
                $searchItems->setData('is_active',1);
                $searchItems->setData('is_processed',0);
                $searchItems->setData('updated_at',$updatedDate);
                $searchItems->save();
            }
            // Pagination functionality 
            if($showPage != "" && $showPage != 0){
                if(isset($page)){
                    $productDataCollection->setPageSize($showPage)->setCurPage($page);
                }
            }
            else{
                if(isset($page)){
                    $productDataCollection->setPageSize($pagination)->setCurPage($page);
                }
            }
            $layeredSizeFilter = [];
            $priceRange = $this->cmsPlpPdp->getSearchableMinMaxPrice($maxPrice, $minPrice, trim($data['keyword']) , $size, $filterableCategoryId);
            $maximumPrice = $priceRange['max_price'];
            $minimumPrice = $priceRange['min_price'];
            $itemDatas['min_price'] = $this->productHelper->getFormattedPrice($minimumPrice);
            $itemDatas['display_min_price'] = $this->productHelper->INDMoneyFormat($minimumPrice);
            $itemDatas['max_price'] = $this->productHelper->getFormattedPrice((int)$maximumPrice);
            $itemDatas['display_max_price'] = $this->productHelper->INDMoneyFormat((int)$maximumPrice);
            $itemDatas['items_per_page'] = count($productDataCollection->getData()) + $filterableGroupedProductCount;
            $categoryList[] = $this->cmsPlpPdp->getSearchResultCategories($maxPrice, $minPrice, trim($data['keyword']) , $size, $filterableCategoryId);
            $itemDatas ['all_category']  = $categoryList;
            $appliedFilter = [];
            $filter = [];
            // Filterable tag
            switch ($tagFilter) {
                case 1:
                    $filteredtag = "On Offer";
                  break;
                case 2:
                    $filteredtag = "New Arrival";
                break;
                default:
                    $filteredtag = "";
            }
            $filter['tag_filter'] = $filteredtag;
            if($maxPrice != 0  && $maxPrice != ''){
                $filter['min_price'] = "";
                if($minPrice != 0){
                    $filter['min_price'] = $minPrice;
                    $filter['display_min_price'] = $this->productHelper->INDMoneyFormat($minPrice);
                }
                $filter['max_price'] = $this->productHelper->getFormattedPrice($maxPrice);
                $filter['displaymax_price'] = $this->productHelper->INDMoneyFormat($maxPrice);
                $filter['default_min_price'] = $this->productHelper->getFormattedPrice($minimumPrice);
                $filter['default_max_price'] = $this->productHelper->getFormattedPrice((int)$maximumPrice);
                $filter['display_default_max_price'] = $this->productHelper->INDMoneyFormat((int)$maximumPrice);
            }
            $filter['size_filter'] = $this->cmsPlpPdp->getSizeByValue($size);
            $filter['filterable_category_name'] = $this->cmsPlpPdp->getCategoryName($filterableCategoryId);
            switch ($sortOrder) {
                case 0:
                    $sortingOrder = "Relevance";
                    break;
                case 1:
                    $sortingOrder = "Price : High to Low";
                    break;
                case 2:
                    $sortingOrder = "Price : Low to High";
                break;
                case 3:
                    $sortingOrder = "Name : A to Z";
                break;
                case 4:
                    $sortingOrder = "Name : Z to A";
                break;
                case 7:
                    $sortingOrder = "New Arrival";
                break;
                default:
                    $sortingOrder = "Relevance";
            }
            $filter['show_page'] = $showPage;
            $filter['page'] = $page;
            $filter['sort_order'] = $sortingOrder;
            $appliedFilter[] = $filter;
            $itemDatas ['available_filter']  = $appliedFilter;
            $itemDatas['layered_filter'] = $this->cmsPlpPdp->getSearchableWeight($maxPrice, $minPrice,trim($data['keyword']) , $size, $filterableCategoryId);
            $sort  = $this->sort->getOptions();
            $itemDatas ['sort_by'] = $sort;
            $plpProduct = [];
            $itemDatas['products'] = [];
            $productDatas = [];
            $itemDatas['tag_filter'] = array_merge($onOfferTagDatas, $newArrivalTagDatas);
            if($tagFilter != ""){
                $itemDatas ['product_count']  = count($tagFilterSpecialPrice['products']);
                $pages = $page - 1;
                if($pages != 0){
                    $from = ($page - 1) * $showPage;
                }
                else{
                    $from = 0;
                }
                //Convert objects to arrays for comparison
                $arrayObjects = array_map('json_decode', array_map('json_encode', $tagFilterSpecialPrice['products']));
                // Remove duplicates based on array comparison
                $uniqueArray = array_map('json_decode', array_map('json_encode', array_unique($arrayObjects, SORT_REGULAR)));
                // Convert arrays back to objects
                $uniqueObjects = array_map('json_decode', array_map('json_encode', $uniqueArray));
                $itemDatas['items_per_page'] = count($uniqueObjects);
                $productItems = array_slice( $uniqueObjects, $from, $showPage );
                foreach($productItems as $productItem){
                    $plpProduct[] = $this->cmsPlpPdp->getProduct($productItem->entity_id);
                }
                $offerSpecialPrice = [];
                $offerPrice = [];
                foreach($uniqueObjects as $product){
                    $productRepo = $this->productRepository->getById($product->entity_id);
                    if(isset($productRepo['special_price']) && $productRepo['special_price'] != null && $productRepo['special_price'] != ""){
                        $offerPrice[] = $this->productHelper->getFormattedPrice($productRepo['special_price']);
                    }
                    else{
                        $offerSpecialPrice[] = $this->productHelper->getFormattedPrice($productRepo['price']);
                    }
                }
                $price = array_merge($offerPrice, $offerSpecialPrice);
                $minMaxPrice = array_filter($price);
                // Category layered navigation filter
                $itemDatas ['all_category']  = $this->cmsPlpPdp->getCategoryTagFilter("", $tagFilterSpecialPrice['products'], $tagFilter, $filterableCategoryId);
                if(!empty($minMaxPrice)){
                    $itemDatas['min_price'] = $this->productHelper->getFormattedPrice(min($minMaxPrice));
                    $itemDatas['display_min_price'] = $this->productHelper->INDMoneyFormat(min($minMaxPrice));
                    $itemDatas['max_price'] = $this->productHelper->getFormattedPrice(max($minMaxPrice));
                    $itemDatas['display_max_price'] = $this->productHelper->INDMoneyFormat(max($minMaxPrice)); 
                }
            }
            else{
                $minprices = [];
                $maxprices = [];
                foreach($productDataCollections as $productMinMaxPrice){
                    $minprices[] = $productMinMaxPrice['minimal_price'];
                    $maxprices[] = $productMinMaxPrice['max_price'];
                }
                $minimumPrice = (!empty($minprices)) ? min($minprices) : 0;
                $maximumPrice =  (!empty($maxprices)) ? max($maxprices) : 0; 
                $itemDatas['min_price'] = $this->productHelper->getFormattedPrice($minimumPrice);
                $itemDatas['display_min_price'] = $this->productHelper->INDMoneyFormat($minimumPrice);
                $itemDatas['max_price'] = $this->productHelper->getFormattedPrice((int)$maximumPrice);
                $itemDatas['display_max_price'] = $this->productHelper->INDMoneyFormat((int)$maximumPrice);
                // search pagination
                $pages = $page - 1;
                if($pages != 0){
                    $from = ($page - 1) * $showPage;
                }
                else{
                    $from = 0;
                }
                $productItems = array_slice( $productDataCollections, $from, $showPage );
                $itemDatas['items_per_page'] = count($productItems);
                foreach($productItems as $product){
                    $plpProduct[] = $this->cmsPlpPdp->getProduct($product['entity_id']);
                }
            }
            $itemDatas['products'] = $plpProduct;
            $itemDatas['meta_title'] = 'search result for '.trim($data['keyword']);
            $itemDatas['meta_keyword']= $this->homePage->getMetaKeywords();
            $itemDatas['meta_description']= $this->homePage->getMetaDescription();
            $plpResponse [] = $itemDatas;
            $responsedata = [
                "code" => 200,
                "status" => true,
                "data" => $plpResponse
            ];
            $response[]  = $responsedata;
        }
        else{
            $responsedata = [
                "code" => 400,
                "status" => false,
                "message" => "Parameter Missing"
            ];
            $response[]  = $responsedata;
        }
        return $response;
    }
}