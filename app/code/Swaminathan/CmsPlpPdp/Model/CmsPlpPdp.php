<?php
namespace Swaminathan\CmsPlpPdp\Model;
use Psr\Log\LoggerInterface;
use Swaminathan\CmsPlpPdp\Api\CmsPlpPdpInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\Cms\Model\PageRepository;
use Swaminathan\CmsPlpPdp\Helper\NoRoute;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use Magento\Catalog\Helper\Image;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Swaminathan\HomePage\Helper\CategoryHelper;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Swaminathan\CmsPlpPdp\Model\Sort;
use Magento\Eav\Model\Config;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Layer\Category\FilterableAttributeList;
use Magento\Catalog\Model\Layer\FilterListFactory;
use Magento\Catalog\Model\Layer\Resolver as layerResolver;
use Magento\Framework\App\State;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProduct;
use Magento\Catalog\Model\ProductCategoryList;
use Magento\Search\Model\QueryFactory;
use Magento\CatalogSearch\Helper\Data as HelperData;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CmsPlpPdp implements CmsPlpPdpInterface
{
    const SIZE_IN_KG = "Weight in Kg";

    const ACTIVE = 1;

    const CATEGORY_ENTITY_TYPE = "category";

    const PAGE_LIMIT = 20;

    const ALL_CATEGORIES = 0;

    const ENABLED = 1;

    const ISINSTOCK = 1;

    const ATTRIBUTE_CODE = "color";

    const SIZE_IN_KG_ATTRIBUTE_CODE = "size_in_kg";

    const NO_SELECTION = "no_selection";

    const MIN_PRICE = 0;

    const VISIBILITY = 1;

    const INCLUDE_IN_MENU = 0;

    const ON_OFFER = "On offer";

    public $productCategory;

    protected $noRoute;

    protected $groupedProduct;

    protected $queryFactory;

    protected $helperData;

    protected $filterableAttributes;

    protected $state;

    protected $layerResolver;

    protected $filterListFactory;

    protected $sort;

    protected $priceCurrency;

    protected $galleryReadHandler;

    protected $categoryCollectionFactory;

    protected $productHelper;

    /**
     * @var Config
     */
    private $eavConvig;

    /**
     * @var Product
     */
    private $product;
    /**
     * @var Category
     */
    private $categoryFactory;
    /**
     * @var CategoryFactory
     */
    private $categoryModelFactory;
    /**
     * @var urlFinderInterface
     */
    private $urlFinderInterface;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var pageRepository
     */
    private $pageRepository;
    /**
     * @var CategoryHelper
     */
    private $categoryHelper;
    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var StockRegistryInterface|null
     */
    private $stockRegistry;

    protected $logger;

    protected $productRepositoryInterface;

    public function __construct(
        ProductRepositoryInterface $productRepositoryInterface,
        ProductHelper $productHelper,
        NoRoute $noRoute,
        LoggerInterface $logger,
        UrlFinderInterface $urlFinderInterface,
        PageRepository $pageRepository,
        ProductRepository $productRepository,
        ProductFactory $productFactory,
        CollectionFactory $productCollectionFactory,
        RequestInterface $request,
        DateTime $date,
        StoreManagerInterface $storeManager,
        UrlHelper $urlHelper,
        Image $imageHelper,
        Configurable $configurable,
        CategoryHelper $categoryHelper,
        CategoryRepository $categoryRepository,
        TimezoneInterface $timezoneInterface,
        Category $categoryFactory,
        CategoryFactory $categoryModelFactory,
        Product $product,
        ReadHandler $galleryReadHandler,
        PriceCurrencyInterface $priceCurrency,
        Sort $sort,
        Config $eavConfig,
        StockRegistryInterface $stockRegistry,
        CategoryCollectionFactory $categoryCollectionFactory,
        FilterableAttributeList $filterableAttributes,
        layerResolver $layerResolver,
        FilterListFactory $filterListFactory,
        State $state,
        GroupedProduct $groupedProduct,
        ProductCategoryList $productCategory,
        QueryFactory $queryFactory,
        HelperData $helperData
    ) {
        $this->productRepositoryInterface = $productRepositoryInterface;
        $this->productHelper = $productHelper;
        $this->noRoute = $noRoute;
        $this->logger = $logger;
        $this->urlFinderInterface = $urlFinderInterface;
        $this->pageRepository = $pageRepository;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->request = $request;
        $this->date = $date;
        $this->storeManager = $storeManager;
        $this->urlHelper = $urlHelper;
        $this->imageHelper = $imageHelper;
        $this->configurable = $configurable;
        $this->categoryHelper = $categoryHelper;
        $this->categoryRepository = $categoryRepository;
        $this->timezoneInterface = $timezoneInterface;
        $this->categoryFactory = $categoryFactory;
        $this->categoryModelFactory = $categoryModelFactory;
        $this->product = $product;
        $this->galleryReadHandler = $galleryReadHandler;
        $this->priceCurrency = $priceCurrency;
        $this->sort = $sort;
        $this->eavConfig = $eavConfig;
        $this->stockRegistry = $stockRegistry;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->filterableAttributes = $filterableAttributes;
        $this->layerResolver = $layerResolver;
        $this->filterListFactory = $filterListFactory;
        $this->state = $state;
        $this->groupedProduct = $groupedProduct;
        $this->productCategory = $productCategory;
        $this->queryFactory = $queryFactory;
        $this->helperData = $helperData;
    }

    // Get parent product ID from grouped child product
    public function getParentProductId($childProductId)
    {
        try {
            // Load the child product
            $childProduct = $this->productRepositoryInterface->getById($childProductId);

            // Get the parent product ID
            $parentId = $this->groupedProduct->getParentIdsByChild($childProduct->getId());

            // The parent product ID will be an array, so extract the first element
            $parentProductId = reset($parentId);

            return $parentProductId;
        } catch (\Exception $e) {
            // Handle exceptions
            return null;
        }
    }

     /**
     * Returns a list of the filtered products, category or cms page.
     * 
     * @param array $data
     * @return array
     */
    public function getContent($data)
    {
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $urlKey = $data['url_key'];
        $tagFilter = $data['tag_filter'] ?? "";
        $minPrice = $data['min_price'] ?? "";
        $maxPrice = $data['max_price'] ?? "";
        $sortOrder = $data['sort_order'] ?? "";
        $page = $data['page'] ?? 1;
        $showPage = $data['show_page'] ?? "";
        $size = $data['size'] ?? "";
        $categoryId = $data['category_id'] ?? "";
        $mainCategoryId = $data['main_category_id'] ?? "";
        $sameCategoryId = $data['same_category_id'] ?? "";
        $filterableCategoryId = $data['filterable_category_id'] ?? "";
        $now = $this->timezoneInterface->date()->format('Y-m-d');
        $today = $this->timezoneInterface->date()->format('Y-m-d 00:00:00');
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $configurableProductLinks = [];
        $configurableProductOptions = [];
        $getQty = [];
        $groupedProducts = [];
        $urlKey = ['request_path' => $urlKey];
        $rewrite = $this->urlFinderInterface->findOneByData($urlKey);
        if(empty($rewrite)){
            $urlKey = ['request_path' => 'no-route'];
            $rewrite = $this->urlFinderInterface->findOneByData($urlKey);
        }
        $type = $rewrite->getEntityType();
        $entityId =  $rewrite->getEntityId();
        $data = [
            'entityType' => $type,
            'entityId' => $entityId,
            'urlKey' => $rewrite->getRequestPath() 
        ];
        if($type == 'cms-page'){
            // get the details of cms page
            $data['content'] = $this->pageRepository->getById($entityId)->getData();
        }else if($type == 'product'){
            // get the details of Product
            $product = $this->productRepository->getById($entityId);
            $productData = $this->productFactory->create()->load($entityId);
            // Get Gallery image
            $mediaGalleryImages = $productData->getMediaGalleryEntries();
            $productDatas = $product->getData();
            if($productDatas['visibility'] != self::VISIBILITY && $productDatas['status'] == self::ENABLED){
                if(isset($categoryId) && $categoryId != "" && $categoryId != 0){
                    $productDetails['breadcrumb'] = $this->getBreadcrumbs($categoryId);
                }
                $metatitle = $productDatas['meta_title'] ?? "";
                $metaKeyword = $productDatas['meta_keyword'] ?? "";
                $metaDescription = $productDatas['meta_description'] ?? "";
                $productDetails['name'] = $productDatas['name'];
                $productDetails['sku'] = $productDatas['sku'];
                $productDetails['product_type'] = $productDatas['type_id'];
                $productDetails['url_key'] = $productDatas['url_key'];
                $productDetails['meta_title'] = $metatitle;
                $productDetails['meta_keyword'] = $metaKeyword;
                $productDetails['meta_description'] = $metaDescription;
                // On offer / New Arrival tag
                $newDate = $this->getNewFromToDate($productDatas['entity_id']);
                $newPrice = $this->getSpecialPriceByProductId($productDatas['entity_id']);
                $tag['new_arrival'] = $newDate['tag']; 
                $tag['on_offer'] = $newPrice['tag']; 
                // grouped product
                if($productDatas['type_id'] == "grouped"){
                    // Get grouped product price
                    $groupedProduct = $this->getGroupedProductPrice($productData);
                    $productDetails['starting_from_price'] = $groupedProduct['starting_from_price'];
                    $productDetails['starting_from_display_price'] = $groupedProduct['starting_from_display_price'];
                    $productDetails['starting_to_price'] = $groupedProduct['starting_to_price'];
                    $productDetails['starting_to_display_price'] = $groupedProduct['starting_to_display_price'];
                    $tag['new_arrival'] = $groupedProduct['tags']['new_arrival'];
                    $tag['on_offer'] = $groupedProduct['tags']['on_offer'];
                    $productDetails['children_count'] = $groupedProduct['children_count'];
                    // Get Stock Status
                    $_children = $productData->getTypeInstance(true)->getAssociatedProducts($productData);
                    $count = 0;
                    foreach($_children as $child){
                        $childItemStockStatus = $child->getId();
                        $groupedQty = $this->productHelper->getSalableQuantity($child->getSku());
                        $isInstockStatus = $this->getStockStatus($child->getId());
                        if ($groupedQty > 0 && $child->getId() != $productData->getId() && $child->getStatus() == self::ENABLED && $isInstockStatus == self::ISINSTOCK) {
                            $count++;
                        }
                    }
                    if($productDatas['quantity_and_stock_status']['is_in_stock'] == true && $count != 0){
                        $productDetails['stock_status'] = "In stock";
                    }
                    else{
                        $productDetails['stock_status'] = "Out of stock";
                    }
                }
                // simple product
                else if($productDatas['type_id'] == "simple"){
                    // get simple product price
                    $specialPriceByProductId = $this->getSpecialPriceByProductId($productDatas['entity_id']);
                    $productDetails['special_price'] = $this->productHelper->getFormattedPrice($specialPriceByProductId['special_price']);
                    $productDetails['display_special_price'] = $this->productHelper->INDMoneyFormat($specialPriceByProductId['special_price']);
                    $productDetails['price'] =  $this->productHelper->getFormattedPrice($productDatas['price']);
                    $productDetails['display_price'] =  $this->productHelper->INDMoneyFormat($productDatas['price']);
                    // get simple producr stock status
                    $qty = $this->productHelper->getSalableQuantity($productDatas['sku']);
                    if($qty > 0){
                        $productDetails['stock_status'] = "In stock";
                    }
                    else{
                        $productDetails['stock_status'] = "Out of stock";
                    }
                }
                // tags
                $productDetails['tag'] = $tag;
                $shortDescription = $productDatas['short_description'] ?? "";
                $productDescription = $productDatas['description'] ?? "";
                $descriptionWithoutCSS = preg_replace('/<style\b[^>]*>(.*?)<\/style>/s', '', $productDescription); // Remove <style> tags
                $descriptionWithoutCSS = preg_replace('/<[^>]+>/', '', $descriptionWithoutCSS); 
                $productDetails['short_description'] = $shortDescription;
                $productDetails['description'] = $descriptionWithoutCSS;
                $weight = $productDatas['weight'] ?? "";
                $productDetails['weight'] = $this->productHelper->getFormattedPrice($weight);
                $productDetails['color'] = $this->productHelper->getColorAttributeValue($productData);
                $productDetails['size_in_kg'] = $this->productHelper->getSizeAttributeValue($productData);
                $productDetails['material'] = $this->productHelper->getMaterialAttributeValue($productData);
                if($productDatas['type_id'] == "simple"){
                $length= $productDatas['length'];
                $productDetails['length'] = number_format((float)$length, 1, '.', '');
                $width = $productDatas['width'];
                $productDetails['width'] = number_format((float)$width, 1, '.', '');
                $height = $productDatas['height'];
                $productDetails['height'] = number_format((float)$height, 1, '.', '');
                }
                $productDetails['product_measurements'] =  "";
                $productDetails['min_quantity'] = $this->getMinSaleQtyById($productData['entity_id']);
                $productDetails['quantity'] = $this->productHelper->getSalableQuantity($productDatas['sku']);
                // Product Measurements Image
                $productDetails['product_measurements']= $productDatas['product_measurement_image_s3_url'] ?? "";
                $productMediaGalleryImages = [];
                if(isset($productDatas['main_image_s3_url']) && $productDatas['main_image_s3_url'] != null && (!empty($productDatas['main_image_s3_url']))){
                    $mainImageUrl['image'] = $productDatas['main_image_s3_url'];
                    $productMediaGalleryImages[] = $mainImageUrl;
                }
                if(isset($productDatas['image1_s3_url']) && $productDatas['image1_s3_url'] != null && (!empty($productDatas['image1_s3_url']))){
                    $image1Url['image'] = $productDatas['image1_s3_url'];
                    $productMediaGalleryImages[] = $image1Url;
                }
                if(isset($productDatas['image2_s3_url']) && $productDatas['image2_s3_url'] != null && (!empty($productDatas['image2_s3_url']))){
                    $image2Url['image'] = $productDatas['image2_s3_url'];
                    $productMediaGalleryImages[] = $image2Url;
                }
                if(isset($productDatas['image3_s3_url']) && $productDatas['image3_s3_url'] != null && (!empty($productDatas['image3_s3_url']))){
                    $image3Url['image'] = $productDatas['image3_s3_url'];
                    $productMediaGalleryImages[] = $image3Url;
                }
                if(isset($productDatas['image4_s3_url']) && $productDatas['image4_s3_url'] != null && (!empty($productDatas['image4_s3_url']))){
                    $image4Url['image'] = $productDatas['image4_s3_url'];
                    $productMediaGalleryImages[] = $image4Url;
                }
                if(empty($productMediaGalleryImages)){
                    $placeHolderImage['image'] = $this->urlHelper->getPlaceHolderImage();
                    $productMediaGalleryImages[] = $placeHolderImage;
                }
                $productMediaGalleryImage['image'] = $productMediaGalleryImages;
                $productImages['image'] = $productMediaGalleryImages;
                $productDetails['media_gallery'] = $productImages;
                $data['product'] = $productDetails;
                //Get Grouped products
                $data['grouped_product'] = [];
                if($productDatas['type_id'] == "grouped"){
                    $groupedProducts = $this->getGroupedProduct($productDatas['entity_id']);
                    $data['grouped_product'] = $groupedProducts;
                }
                // Get Related Products 
                $data['related_product'] = [];
                $relatedProductArray = $this->getRelatedProductsList($productDatas['entity_id']);
                if(count($relatedProductArray) > 0){
                    $data['related_product'] = $relatedProductArray;
                }
                $categoryIds = $productDatas['category_ids'];
                // Get From Same Category
                $data['from_same_category'] = [];
                $sameCategory = $this->getSameCatgoryProducts($sameCategoryId, $categoryIds, $productDatas['entity_id']);
                if(count($sameCategory) > 0){
                    $data['from_same_category'] = $sameCategory;
                }
            }
            else{
                return $this->noRoute->getNoRoute();
            }
        }else if($type == 'category'){
            $category = $this->categoryRepository->get($entityId);
            $categoryDatas = $category->__toArray();
            $categories = $this->categoryModelFactory->create()->load($entityId);
            $parentCatId = $categories->getData()['parent_id'];
            $categoriesData = $categories->getData();
            // Get category meta title
            $metaTitle = $categoriesData['meta_title'] ?? "";
            $data['meta_title'] = $metaTitle;
            // Get category meta title
            $metaKeywords = $categoriesData['meta_keywords'] ?? "";
            $data['meta_keywords'] = $metaKeywords;
            // Get category meta description
            $metaDescription = "";
            $metaDescription = $categoriesData['meta_description'] ?? "";
            $data['meta_description'] = $metaDescription;
            $data['breadcrumb'] = $this->getBreadcrumbs($entityId);
            $urlKey = $categoryDatas['url_key'];
            $categoryName = $categoryDatas['name'];
            // get the details of category
            if($categoryDatas['is_active'] == self::ACTIVE){
                $productDataCollection = $categories->getProductCollection();
                $productDataCollection->addAttributeToSelect('min_price'); 
                $productDataCollection->addAttributeToSelect('status'); 
                $productDataCollection->addAttributeToSelect('visibility'); 
                $productDataCollection->addAttributeToSelect('size_in_kg'); 
                $productDataCollection->addAttributeToSelect('price'); 
                $productDataCollection->addAttributeToSelect('name'); 
                // status Filter
                $productDataCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); // Filter enable product
                // // Visibility Filter
                $productDataCollection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)); 
                if(isset($maxPrice) && $maxPrice != '' && $maxPrice != 0 && isset($minPrice) && $minPrice != ''){
                    $productDataCollection->addPriceDataFieldFilter('%s >= %s', ['max_price', $minPrice]);
                    $productDataCollection->addPriceDataFieldFilter('%s <= %s', ['min_price', $maxPrice]);
                    $productDataCollection->addMinimalPrice();
                }
                $filterableGroupedProducts = [];
                $filterableGroupedProductCount = 0;
                if($size != "" && $size != 0){
                    $linkTypeId  = 3;
                    $productDataCollection->addAttributeToFilter(
                        [
                            ['attribute'=>'size_in_kg','eq'=> $size] // size filter
                        ]
                    );
                    $_productDataCollection = $categories->getProductCollection();
                    $_productDataCollection->addAttributeToSelect('size_in_kg');
                    $_productDataCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); // Filter enable product
                    $_productDataCollection->addAttributeToFilter('type_id',array('eq' => 'grouped'));
                    if(count($_productDataCollection->getData()) > 0){
                        foreach($_productDataCollection->getData() as $parentProduct){
                            $parentProductId = $parentProduct['entity_id'];
                            $childProducts = $this->getGroupedProduct($parentProductId);
                            foreach ( $childProducts as $childProduct ) {
                                if ($size == $childProduct['size_in_kg_value'] ) {
                                    $filterableGroupedProduct['entity_id'] = $this->getParentProductId($childProduct['id']);
                                    $filterableGroupedProducts[] = $filterableGroupedProduct;
                                }
                            }
                        
                        }
                    }
                    if(count($filterableGroupedProducts)){
                        $filterableGroupedProductCount = count($filterableGroupedProducts);
                    }
                }
                // sort order functionality relevance, price low to high, price high to low, sort by name ASC and sort by name DESC
                switch ($sortOrder) {
                    case Sort::PRICE_LOW_TO_HIGH:
                        $productDataCollection->setOrder('price', 'ASC');
                    break;
                    case Sort::PRICE_HIGH_TO_LOW:
                        $productDataCollection->setOrder('price', 'DESC');
                    break;
                    case Sort::NEW_ARRIVAL:
                        $productDataCollection->addAttributeToSort('entity_id','desc');
                    break;
                    case Sort::NAME_ASC:
                        $productDataCollection->addAttributeToSort('name', 'ASC');
                    break;
                    case Sort::NAME_DESC:
                        $productDataCollection->addAttributeToSort('name', 'DESC');
                    break;
                    default:
                    
                }
                // Tag layered navigation
                $data['tag_filter'] = [];
                $onOfferTagDatas = [];
                $newArrivalTagDatas = [];
                $filterOfferArrivalTag = $this->getTagFilter($productDataCollection, $tagFilter);
                if(!empty($filterOfferArrivalTag['on_offer'])){
                    $onOffer['on_offer'] = $filterOfferArrivalTag['on_offer'];
                    $onOfferTagDatas = $onOffer;
                }
                if(!empty($filterOfferArrivalTag['new_arrival'])){
                    $newArrival['new_arrival'] = $filterOfferArrivalTag['new_arrival'];
                    $newArrivalTagDatas = $newArrival;
                }
                // Pagination 
                $itemPerPage = $this->urlHelper->getProductPerPage();
                $pageLimit = self::PAGE_LIMIT;
                $pagination = ($itemPerPage == "" || $itemPerPage == 0) ? $pageLimit :$itemPerPage;
                $productInCategory = count($productDataCollection) + $filterableGroupedProductCount;
                $data ['product_count']  = $productInCategory;
                // Get Maximun price from product collections
                $maximumPrice = ""; 
                $minimumPrice = "";
                if($size != "" && $size != 0){
                    $maxMinPrice = $this->maxMinPriceFilter($entityId, $size);
                    $maximumPrice = $maxMinPrice['highest_price']; 
                    $minimumPrice = $maxMinPrice['lowest_price'];
                }
                else{
                    $maxProductDatacollections = $categories->getProductCollection();
                    $maxProductDatacollections->addAttributeToSelect('price');
                    $maxProductDatacollections->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); // Filter enable product
                    if(!empty($maxProductDatacollections->getData())){
                        $maximumPriceCollection = $maxProductDatacollections->setOrder('price', 'DESC')->getFirstItem();
                        $maxData = $maximumPriceCollection->getData();
                        if($maxData['type_id'] == "grouped"){
                            $maximumPrice = $maximumPriceCollection->getData()['max_price'];
                        }
                        else if($maxData['type_id'] == "simple"){
                            $maximumPrice = $maximumPriceCollection->getData()['final_price'];
                        }
                        else{
                            $maximumPrice = $maximumPriceCollection->getData()['final_price'];
                        }
                    }
                    $minProductDatacollections = $categories->getProductCollection();
                    $minProductDatacollections->addAttributeToSelect('price');
                    $minProductDatacollections->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); // Filter enable product
                    if(!empty($minProductDatacollections->getData())){
                        $minimumPrice = $minProductDatacollections->setOrder('price', 'ASC')->getFirstItem();
                        $minData = $minimumPrice->getData();
                        if($minData['type_id'] == "grouped"){
                            $minimumPrice = $minimumPrice->getData()['min_price'];
                        }
                        else if($minData['type_id'] == "simple"){
                            $minimumPrice = $minimumPrice->getData()['final_price'];
                        }
                        else{
                            $minimumPrice = $minimumPrice->getData()['final_price'];
                        }
                    }
                }
                
                $data['min_price'] = $this->productHelper->getFormattedPrice((int)$minimumPrice);
                $data['display_min_price'] = $this->productHelper->INDMoneyFormat((int)$minimumPrice);
                $data['max_price'] = $this->productHelper->getFormattedPrice((int)$maximumPrice);
                $data['display_max_price'] = $this->productHelper->INDMoneyFormat((int)$maximumPrice);
                $data ['category_name']  = $categoryName;
                $data['items_per_page'] = count($productDataCollection->getData()) + $filterableGroupedProductCount;
                $data ['all_category']  = $this->getSubCategoriesById($maxPrice, $minPrice, $entityId, $size, $productInCategory); 
                // Applied available filters
                $appliedFilter = [];
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
                $filter['show_page'] = $showPage;
                $filter['page'] = $page;
                $filter['tag_filter'] = $filteredtag;
                // Filtered size 
                $sizeValue = $this->getSizeLabelByValue($size);
                $filter['size_filter'] = $sizeValue['size'];
                $filter['default_size_filter'] = ""; 
                if($maxPrice != 0  && $maxPrice != ''){
                    $filter['min_price'] = "";
                    $filter['display_min_price'] = "";
                    if($minPrice != 0){
                        $filter['min_price'] = $minPrice;
                        if($minPrice != ""){
                            $filter['display_min_price'] = $this->productHelper->INDMoneyFormat($minPrice);
                        }
                    }
                    $filter['max_price'] = $maxPrice;
                    $filter['displaymax_price'] = $this->productHelper->INDMoneyFormat($maxPrice);
                    $filter['default_min_price'] = $this->productHelper->getFormattedPrice($minimumPrice);
                    $filter['display_default_min_price'] = $this->productHelper->INDMoneyFormat($minimumPrice);
                    $filter['default_max_price'] = $this->productHelper->getFormattedPrice((int)$maximumPrice);
                    $filter['display_default_max_price'] = $this->productHelper->INDMoneyFormat((int)$maximumPrice);
                }
                $filter['filterable_category_name'] = "";
                $filter['filterable_category_url'] = "";
                if(!empty($filterableCategoryId)){
                    $filter['filterable_category_name'] = $this->getCategoryName($filterableCategoryId);
                    $filterableCategories = $this->categoryModelFactory->create()->load($filterableCategoryId);
                    $filterableParentCatId = $filterableCategories->getData()['parent_id'];
                    if($filterableParentCatId != $root){
                        $filter['filterable_category_url'] = $this->productHelper->getCategoryRewriteUrl($filterableParentCatId);
                    }
                }
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
                $filter['sort_order'] = $sortingOrder;
                $appliedFilter[] = $filter;
                $data ['available_filter']  = $appliedFilter;
                $data['layered_filter'] = $this->getSearchableWeight($maxPrice, $minPrice, "", $size, $entityId);
                $sort  = $this->sort->getOptions();
                $data ['sort_by'] = "";
                if(!empty($sort)){
                    $data ['sort_by'] = $sort;
                }
                $plpProduct = [];
                $data['products'] = [];
                $productDatas = [];
                // get tag price filter
                $tagFilterSpecialPrice = $this->getTagPriceFilter($productDataCollection, $tagFilter);
                if((!empty($filterableGroupedProducts))){
                    $productDataCollections = (array) array_merge($filterableGroupedProducts, $productDataCollection->getData());
                }
                else{
                    $productDataCollections = $productDataCollection->getData();
                }
                $data['tag_filter'] = array_merge($onOfferTagDatas, $newArrivalTagDatas);
                $offerPrice = [];
                if($tagFilter != ""){
                    $data ['product_count']  = count($tagFilterSpecialPrice['products']);
                    // Weight filter with tags
                    // Category layered navigation filter
                    $data ['all_category']  = $this->getCategoryTagFilter($entityId, $tagFilterSpecialPrice['products'], $tagFilter, $filterableCategoryId);
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
                    $productItems = array_slice( $uniqueObjects, $from, $showPage );
                    $data['items_per_page'] = count($productItems);
                    foreach($productItems as $productItem){
                        $plpProduct[] = $this->getProduct($productItem->entity_id);
                    }
                    $offerSpecialPrice = [];
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
                    if(!empty($minMaxPrice)){
                        $data['min_price'] = $this->productHelper->getFormattedPrice(min($minMaxPrice));
                        $data['display_min_price'] = $this->productHelper->INDMoneyFormat(min($minMaxPrice));
                        $data['max_price'] = $this->productHelper->getFormattedPrice(max($minMaxPrice));
                        $data['display_max_price'] = $this->productHelper->INDMoneyFormat(max($minMaxPrice)); 
                    }
                }
                else{
                    $pages = $page - 1;
                    if($pages != 0){
                        $from = ($page - 1) * $showPage;
                    }
                    else{
                        $from = 0;
                    }
                    $productItems = array_slice( $productDataCollections, $from, $showPage );
                    $data['items_per_page'] = count($productItems);
                    if(count($productItems) > 0){
                        foreach($productItems as $product){
                            if(isset($product['product_id'])){
                                $productId = $product['product_id'];
                            }
                            else{
                                $productId = $product['entity_id'];
                            }
                            $plpProduct[] = $this->getProduct($productId);
                        }
                    }
                }
                $data['products'] = $plpProduct;
            }
            else{
                $data = [
                    "code" => 400,
                    "status" => false,
                    "message" => "Category is inactive mode."
                ];
            }
        }
        $response [] = $data;
        $responseData[] = [
            "code" => 200,
            "status" => true,
            "data" => $response
        ];
        return $responseData;
    }
    // get products
    public function getProduct($productId){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $specialPrice = "";
        $newArrivalOnOfferTags = "";
        $productWeight = "";
        $shortDesc = "";
        $productPosition = "";
        $productRewriteUrl = $this->productHelper->getProductRewriteUrl($productId);
        $productDatas = $this->productRepository->getById($productId);
        $productData = $productDatas->getData();
        // Product Name
        $productName = $productData['name'];
        // Product SKU
        $productSku = $productData['sku'];
        // Product Type
        $productType = $productData['type_id'];
        // Get product tag
        $onArrival = $this->getNewFromToDate($productData['entity_id']);
        $specialPriceByProductId = $this->getSpecialPriceByProductId($productData['entity_id']);
        $tag['new_arrival'] = $onArrival['tag'];
        $tag['on_offer'] = $specialPriceByProductId['tag'];
        // Product Price
        $productShowPrice = "";
        $productPrice = "";
        $showSpecialPrice = "";
        $startingFromPrice = "";
        $startingFromShowPrice = "";
        $startingToPrice = "";
        $startingToShowPrice = "";
        $prices = [];
        $specialPrices = [];
        $showPrice = "";
        // get simple producr stock status
        $qty = $this->productHelper->getSalableQuantity($productData['sku']);
        if($productType == "grouped"){
            $groupedProducts = $this->getGroupedProductPrice($productDatas);
            $startingFromPrice = $groupedProducts['starting_from_price'];
            $startingFromShowPrice = $groupedProducts['starting_from_display_price'];
            $startingToPrice = $groupedProducts['starting_to_price'];
            $startingToShowPrice = $groupedProducts['starting_to_display_price'];
            $tag['new_arrival'] = $groupedProducts['tags']['new_arrival'];
            $tag['on_offer'] = $groupedProducts['tags']['on_offer'];
            $childrenCount = $groupedProducts['children_count'];
            // Get Stock Status
            $_children = $productDatas->getTypeInstance(true)->getAssociatedProducts($productDatas);
            $count = 0;
            foreach($_children as $child){
                $childItemStockStatus = $child->getId();
                $isInstockStatus = $this->getStockStatus($child->getId());
                $groupedchildSku = $this->productHelper->getSalableQuantity($child->getSku());
                if ($groupedchildSku > 0 && $child->getId() != $productDatas->getId() && $child->getStatus() == self::ENABLED && $isInstockStatus == self::ISINSTOCK) {
                    $count++;
                }
            }
            if($productDatas['quantity_and_stock_status']['is_in_stock'] == true && $count != 0){
                $stockStatus = "In stock";
            }
            else{
                $stockStatus = "Out of stock";
            }
        }
        else if($productType == "simple"){
            $specialPriceByProductId = $this->getSpecialPriceByProductId($productData['entity_id']);
            $specialPrice = $this->productHelper->getFormattedPrice($specialPriceByProductId['special_price']);
            $showSpecialPrice = $this->productHelper->INDMoneyFormat($specialPriceByProductId['special_price']);
            $productPrice =  $this->productHelper->getFormattedPrice($productData['price']);
            $productShowPrice =  $this->productHelper->INDMoneyFormat($productData['price']);
            if($qty > 0 && isset($productData['quantity_and_stock_status']['is_in_stock']) && $productData['quantity_and_stock_status']['is_in_stock'] == true){
                $stockStatus = "In stock";
            }
            else{
                $stockStatus = "Out of stock";
            }
        }
        if(!empty($tag)){
            $newArrivalOnOffertags = $tag;
        }
        // Get Product Weight
        if(isset($productData['weight']) && $productData['weight'] != "" && $productData['weight'] != null){
            $productWeight = $this->productHelper->getFormattedPrice($productData['weight']); 
        }
        // Product Short Description
        $shortDesc = $productData['short_description'] ?? "";
        $productData = $this->productFactory->create()->load($productData['entity_id']);
        // Product Size
        $size_in_kg = $this->productHelper->getSizeAttributeValue($productData);
        // Product Color Atrribute Value
        $productColor = $this->productHelper->getColorAttributeValue($productData);
        $products ['id'] = $productData['entity_id'];
        $products ['name'] = $productName;
        $products ['type_id'] = $productType;
        $products ['sku'] = $productSku;
        $products ['price'] = $productPrice;
        $products ['display_price'] = $productShowPrice;
        $products ['starting_from_price'] = $startingFromPrice;
        $products ['starting_from_display_price'] = $startingFromShowPrice;
        $products ['starting_to_price'] = $startingToPrice;
        $products ['starting_to_display_price'] = $startingToShowPrice;
        $products ['size_in_kg'] = $size_in_kg;
        $products ['display_special_price'] = $showSpecialPrice;
        $products ['special_price'] = $specialPrice;
        $products ['tag'] = $newArrivalOnOffertags;
        $products ['weight'] = $productWeight;
        $products ['color'] = $productColor;
        $products ['stock_status'] = $stockStatus;
        $products ['short_description'] = $shortDesc;
        $products["min_qty"] = $this->getMinSaleQtyById($productData['entity_id']);
        $products['material'] = $this->productHelper->getMaterialAttributeValue($productData);
        if($productDatas['type_id'] == "simple"){
            $length= $productDatas['length'];
            $products['length'] = number_format((float)$length, 1, '.', '');
            $width = $productDatas['width'];
            $products['width'] = number_format((float)$width, 1, '.', '');
            $height = $productDatas['height'];
            $products['height'] = number_format((float)$height, 1, '.', '');
            }
        $products ['quantity'] = $qty;
        $products ['url_key'] = $productRewriteUrl;
        if($productType == "configurable"){
            $products ['children_count'] = $childrenCount;
        }else if($productType == "grouped"){
            $products ['children_count'] = $childrenCount;
        }
        // Get Media Gallery Images
        $products['media_gallery'] = $this->GetMediaGalleryImage($productData);
        // Get Configurable Option Values
        $configurableOptions = [];
        if($productData['type_id'] == "configurable"){
            $configurableOptions = $this->getConfigurableProductById($productData['entity_id']);
        }
        $products ['configurable_product'] =  $configurableOptions;
        
        // Get Grouped Product
        $groupedProductDatas = [];
        if($productData['type_id'] == "grouped"){
            $groupedProductDatas = $this->getGroupedProduct($productData['entity_id']);
            $products ['grouped_product'] =  $groupedProductDatas;
        }
        $products ['grouped_product'] =  $groupedProductDatas;
        return $products;
    }
    // Get breadcrumb
    public function getBreadcrumbs($category_id) {
        if($category_id != 0) {
            $categories = $this->categoryModelFactory->create()->load($category_id);
            $breadcrumb = [];
            if(!$categories->getId()) {
                return $breadcrumb;
            }
            $lastCatInfo = [];
            $lastCatInfo['label'] = $categories->getName();
            $lastCatInfo['link'] = $this->productHelper->getCategoryRewriteUrl($categories->getId());
            $lastCatInfo['bulk_order'] = $categories->getBulkOrder();
            $breadcrumb = [];
            foreach ($categories->getParentIds() as $parent) {
                $parentCategories = $this->categoryModelFactory->create()->load($parent);
                if($parentCategories->getLevel() >= 2) {
                    $catbreadcrumb = array("label" => $parentCategories->getName(), "link" => $this->productHelper->getCategoryRewriteUrl($parentCategories->getId()));
                    array_push($breadcrumb, $catbreadcrumb);
                }
            }
            array_push($breadcrumb, $lastCatInfo);
            return $breadcrumb;
        }
        return $breadcrumb;
    }
    // get category name by category id
    public function getCategoryName($categoryId)
    {
        $category = $this->categoryModelFactory->create()->load($categoryId);
        $categoryName = $category->getName();
        return $categoryName;
    }
    // Get filterable product Collections
    public function getFilterableGroupedProduct($parentIds){
        $groupedProductCollections = $this->productCollectionFactory->create();
        $groupedProductCollections->addAttributeToFilter('type_id','grouped');
        $groupedProductCollections->addAttributeToFilter('entity_id', array('in' => $parentIds));
        $groupedProductCollections->addFinalPrice();
        return $groupedProductCollections->getData();
    }
    // Get all size by category
    public function getAllSize($categoryId, $sizeFilter){
        $filterArray['size_in_kg'] = "";
        $filterArray = [];
        $filterableAttributes = $this->filterableAttributes;
        $appState = $this->state;
        $layerResolver = $this->layerResolver;
        $filterList = $this->filterListFactory->create(['filterableAttributes' => $filterableAttributes]);
        $layer = $layerResolver->get();
        $layer->setCurrentCategory($categoryId);
        $filters = $filterList->getFilters($layer);
        $productCollection = $layer->getProductCollection()
            ->addAttributeToFilter("visibility", ["neq" => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter("status", ["eq" => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED]);
        if($sizeFilter != 0 && $sizeFilter != ""){
            $productCollection->addAttributeToFilter(
                [
                    ['attribute'=>'size_in_kg','eq'=> $sizeFilter] // size filter
                ]
            );
        }
        $maxPrice = $layer->getProductCollection()->getMaxPrice();
        $minPrice = $layer->getProductCollection()->getMinPrice();  
        $i = 0;
        foreach($filters as $filter)
        {
            $availablefilter = (string)$filter->getName(); 
            $items = $filter->getItems();
            $filterValues = array();
            $j = 0;
            foreach($items as $item)
            {
                $filterValues[$j]['display'] = strip_tags($item->getLabel());
                $filterValues[$j]['value']   = $item->getValue();
                $filterValues[$j]['count']   = $item->getCount(); 
                $j++;
            }
            if($availablefilter == "Weight in Kg"){
                if(!empty($filterValues) && count($filterValues)>1)
                {
                    $filterArray['size_in_kg'] =  $filterValues;
                }
            }
            $i++;
        }  
        $filters = $filterList->getFilters($layer);
        return $filterArray;
    }
    public function getSizeLabelByValue($size){
        $productArrayData = $this->getSize();
        $sizeValue = '';
        $values['size'] = '';
        foreach($productArrayData as $sizeLabel){
            if($sizeLabel['value'] == $size) {
                $sizeValue = $sizeLabel['label'];
            }
            if($sizeValue != ''){
                $values['size'] = $sizeValue;
            }
        }
        return $values;
    }

    public function getSizeByValue($size){
        $productArrayData = $this->getSize();
        $sizeValue = '';
        $values = '';
        foreach($productArrayData as $sizeLabel){
            if($sizeLabel['value'] == $size) {
                $sizeValue = $sizeLabel['label'];
            }
            if($sizeValue != ''){
                $values = $sizeValue;
            }
        }
        return $values;
    }
    public function getSize(){
        try {
            $attribute = $this->eavConfig->getAttribute('catalog_product', self::SIZE_IN_KG_ATTRIBUTE_CODE);
            $options = $attribute->getSource()->getAllOptions();
            foreach($options as $option){
                if($option['value'] != ""){
                    $response[] = $option;
                }
                else{
                    $response = [];
                }
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $response = $e->getMessage();
            $this->logger->info('size_in_kg atrribute is not found'.$e->getMessage());
        }
        return $response;
    }
    
    // Get Configurable Product
    public function getConfigurableProductById($productId){
        $sizeInKg = "";
        $specialPrice = "";
        $productWeight = "";
        $itemPrice = "";
        $specialShowPrice = "";
        $product = $this->productRepository->getById($productId);
        $productData = $this->productFactory->create()->load($productId);
        // Configurable Product Link
        $storeId = $this->storeManager->getStore()->getId();
        $productTypeInstance = $product->getTypeInstance();
        $productTypeInstance->setStoreFilter($storeId, $product);
        $usedProducts = $productTypeInstance->getUsedProducts($product);
        foreach ($usedProducts as $child) {
            $configurableProductLinks[] = $child->getId();
        }
        // Configurable Product Options
        $productTypeInstance= $this->configurable;
        $productAttributeOptions = $productTypeInstance->getConfigurableAttributesAsArray($productData);
        foreach ($productAttributeOptions as $key => $value) {
            $configProductSize = $value['values'];
            $configVariant = [];
            foreach($configProductSize as $configVariant){
                foreach($configurableProductLinks as $configurableProductLink){
                    $itemData = $this->productFactory->create()->load($configurableProductLink);
                    $itemDatas = $itemData->getData();
                    $getQty = $this->productHelper->getSalableQuantity($itemDatas['sku']);
                    if(!empty($this->productHelper->getSizeAttributeValue($itemData))){
                        $sizeInKg = $this->productHelper->getSizeAttributeValue($itemData);
                    }
                    $newPrice = $this->getSpecialPriceByProductId($configurableProductLink);
                    $specialPrice = $this->productHelper->getFormattedPrice($newPrice['special_price']);
                    $specialShowPrice = $this->productHelper->INDMoneyFormat($newPrice['special_price']); 
                    $isInStockStatus = $this->productHelper->getSalableQuantity($itemDatas['sku']);
                    if($getQty > 0){
                        $stockStatus = "In stock";
                    }
                    else{
                        $stockStatus =  "Out of stock";
                    }
                    if(isset($itemDatas['weight']) && $itemDatas['weight'] != "" && $itemDatas['weight'] != null){
                        $productWeight = $this->productHelper->getFormattedPrice($itemDatas['weight']);
                    }
                    $itemShowPrice = "";
                    if($itemDatas['status'] == self::ENABLED){
                        $itemPrice = $this->productHelper->getFormattedPrice($itemDatas['price']);
                        $itemShowPrice = $this->productHelper->INDMoneyFormat($itemDatas['price']);
                        $productRewriteUrl = $this->productHelper->getProductRewriteUrl($itemDatas['entity_id']);
                        $configurableProductOptions['id'] = $itemDatas['entity_id'];
                        $configurableProductOptions['name'] = $itemDatas['name'];
                        $configurableProductOptions['sku'] = $itemDatas['sku'];
                        $configurableProductOptions['size'] = $sizeInKg;
                        $configurableProductOptions['price'] = $itemPrice;
                        $configurableProductOptions['display_price'] = $itemShowPrice;
                        $configurableProductOptions['special_price'] = $specialPrice;
                        $configurableProductOptions['display_special_price'] = $specialShowPrice;
                        $configurableProductOptions['weight'] = $productWeight;
                        $groupedProducts["min_quantity"] = $this->getMinSaleQtyById($itemDatas['entity_id']);
                        $configurableProductOptions['quantity'] = $getQty;
                        $configurableProductOptions['stock_status'] = $stockStatus;
                        $configurableProductOptions['url_key'] = $productRewriteUrl;
                    }
                    $configSize[] = $configurableProductOptions;
                }
                return $configSize;
            }
        }
    }
    // get stock status
    public function getStockStatus($productId)
    {
        /** @var StockItemInterface $stockItem */
        $stockItem = $this->stockRegistry->getStockItem($productId);
        $isInStock = $stockItem ? $stockItem->getIsInStock() : false;
        return $isInStock;
    }
    // get Minimum Scale qunatity
    public function getMinSaleQtyById($productId)
    {
        /** @var StockItemInterface $stockItem */
        $stockItem = $this->stockRegistry->getStockItem($productId);
        $isInStock = $stockItem->getMinSaleQty();
        return $isInStock;
    }
    
    // Get Grouped Product 
    public function getGroupedProduct($productId){
        $product = $this->productRepository->getById($productId);
        $productData = $this->productFactory->create()->load($productId);
        $_children = $productData->getTypeInstance(true)->getAssociatedProducts($productData);
        $groupedProductDatas = [];
        foreach ($_children as $child) {
            $specialPrice = "";
            $sizeInKg = "";
            $sizeInKgValue = "";
            $productPrice = "";
            $productShowPrice = "";
            $showSpecialPrice = "";
            $groupedProductName = "";
            $isInstockStatus = $this->getStockStatus($child->getId());
            if ($child->getId() != $productData->getId() && $child->getStatus() == self::ENABLED) {
                $groupedProductSku = $child->getSku();
                $newPrice = $this->getSpecialPriceByProductId($child->getId());
                $childProductData = $this->productFactory->create()->load($child->getId());
                $sizeInKg = $this->productHelper->getSizeAttributeValue($childProductData);
                $sizeInKgValue = $childProductData['size_in_kg'];
                $specialPrice = $this->productHelper->getFormattedPrice($newPrice['special_price']);
                $showSpecialPrice = $this->productHelper->INDMoneyFormat($newPrice['special_price']);
                $productPrice = $this->productHelper->getFormattedPrice($child->getPrice());
                $productShowPrice = $this->productHelper->INDMoneyFormat($child->getPrice());
                $child_material = $this->productHelper->getMaterialAttributeValue($childProductData);
                $length= $childProductData['length'];
                $productLength = number_format((float)$length, 1, '.', '');
                $width = $childProductData['width'];
                $productWidth = number_format((float)$width, 1, '.', '');
                $height = $childProductData['height'];
                $productHeight = number_format((float)$height, 1, '.', '');
                if(isset($childProductData['main_image_s3_url']) && $childProductData['main_image_s3_url'] != null && (!empty($childProductData['main_image_s3_url']))){
                    $groupedProductImage = $childProductData['main_image_s3_url'];
                }
                else{
                    $groupedProductImage = $this->urlHelper->getPlaceHolderImage();
                }
                $groupedProductName = $child->getName();
                $getQty = $this->productHelper->getSalableQuantity($childProductData['sku']);
                if($isInstockStatus == self::ISINSTOCK){
                    $stockStatus = "In stock";
                }
                else{
                    $stockStatus = "Out of stock";
                }
                $productRewriteUrl = $this->productHelper->getProductRewriteUrl($child->getId());
                $groupedProducts["id"] = $child->getId();
                $groupedProducts["name"] = $groupedProductName;
                $groupedProducts["sku"] = $groupedProductSku;
                $groupedProducts["image"] = $groupedProductImage;
                $groupedProducts["price"] = $productPrice;
                $groupedProducts["display_price"] = $productShowPrice;
                $groupedProducts["special_price"] = $specialPrice;
                $groupedProducts["display_special_price"] = $showSpecialPrice;
                $groupedProducts["min_quantity"] = $this->getMinSaleQtyById($child->getId());
                $groupedProducts['selected_qty'] = "";
                $groupedProducts["quantity"] = $getQty;
                $groupedProducts["stock_status"] = $stockStatus;
                $groupedProducts['size_in_kg'] = $sizeInKg;
                $groupedProducts["child_material"] = $child_material;
                $groupedProducts['length'] = $productLength;
                $groupedProducts['width'] = $productWidth;
                $groupedProducts['height'] = $productHeight;
                $groupedProducts['size_in_kg_value'] = $sizeInKgValue;
                $groupedProducts["url_key"] = $productRewriteUrl;
                $groupedProductDatas[] = $groupedProducts;
            }
        }
        return $groupedProductDatas;
    }

    // Get Special Price by product id
    public function getSpecialPriceByProductId($productId)
    {
        $specialPrice['tag'] = "";
        $specialPrice['special_price'] = ""; 
        $todayDate = $this->timezoneInterface
                                            ->date()
                                            ->format('Y-m-d');
        $itemData = $this->productFactory->create()->load($productId);
        $item = $itemData->getData();
        $specialFromDate = "";
        $specialToDate = "";
        if((isset($item['special_from_date'])) && (isset($item['special_to_date']))){
            $specialFromDate = $this->timezoneInterface
                                        ->date(new \DateTime($item['special_from_date']))
                                        ->format('Y-m-d');
            $specialToDate = $this->timezoneInterface
                                        ->date(new \DateTime($item['special_to_date']))
                                        ->format('Y-m-d');
        }
        if((isset($item['special_from_date'])) && (isset($item['special_to_date'])) && $specialFromDate <= $todayDate && $specialToDate >= $todayDate && $item['special_from_date'] != "" && $item['special_to_date'] != ""){
            if(isset($item['special_price']) && $item['price'] > $item['special_price']){
                $specialPrice['tag'] = "On Offer"; 
                $specialPrice['special_price'] = $item['special_price'];
            }
        }
        else if((!isset($item['special_from_date'])) || (!isset($item['special_to_date']))){
            if(isset($item['special_price']) && $item['price'] > $item['special_price']){
                $specialPrice['tag'] = "On Offer"; 
                $specialPrice['special_price'] = $item['special_price'];
            }
        }
        return $specialPrice;
    }
    // Get New Arrival tag from new from and to date.
    public function getNewFromToDate($productId){
        $now = $this->timezoneInterface
                                            ->date()
                                            ->format('Y-m-d');
        $itemData = $this->productFactory->create()->load($productId);
        $productDatas = $itemData->getData();
        // On offer / New Arrival tag
        if(isset($productDatas['news_from_date']) && $productDatas['news_from_date'] != "" && isset($productDatas['news_to_date']) && $productDatas['news_to_date'] != ""){
            // Adding on offer tag for new from date
            $newsFromDate = $this->timezoneInterface
                                        ->date(new \DateTime($productDatas['news_from_date']))
                                        ->format('Y-m-d');
            $newsToDate = $this->timezoneInterface
                                        ->date(new \DateTime($productDatas['news_to_date']))
                                        ->format('Y-m-d');
            if($newsFromDate <= $now && $newsToDate >= $now){
                $productDetails['tag'] = "New Arrival"; 
            }
            else{
                $productDetails['tag'] = ""; 
            }
        }
        else{
            $productDetails['tag'] = ""; 
        }
        return $productDetails;
    }

    public function getParentCategories($productId){
        $productCategory = $this->productFactory->create()->load($productId);
        $categoryIds = $productCategory->getCategoryIds();
        foreach($categoryIds as $categoryId){
            $parCategory = $this->categoryFactory->load($categoryId);
            $parent_category = $parCategory->getParentCategory();
            $catName[] = $parent_category->getName();
        }
    }
    /* Get category object */
    public function getCategory($categoryId)
    {
        $category = $this->categoryModelFactory->create();
        $category->load($categoryId);
        return $category;
    }
    /* Product collection by category id */
    public function getProductCollectionCount($categoryId){
        $categories = $this->categoryModelFactory->create()->load($categoryId);
        $productCollection = $categories->getProductCollection();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); // Filter enable product
        // Product Visibility
        $productCollection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)); 
        return count($productCollection->getData());
    }

    // Get maximum and minimum price 
    public function maxMinPriceFilter($categoryId, $size){
        $maxPrice = "";
        $minPrice = "";
        $filterableAttributes = $this->filterableAttributes;
        $appState = $this->state;
        $layerResolver = $this->layerResolver;
        $filterList = $this->filterListFactory->create(['filterableAttributes' => $filterableAttributes]);
        $layer = $layerResolver->get();
        $filters = $filterList->getFilters($layer);
        $layer->setCurrentCategory($categoryId);
        $productCollection = $layer->getProductCollection()
            ->addAttributeToFilter("visibility", ["neq" => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter("status", ["eq" => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED]);
        $productData = $layer->getProductCollection();
        $groupedProductfinalPrice = [];
        if(count($productData) > 0 && $size != "" && $size != 0){
            foreach($productData as $product){
                if($product['type_id'] == "simple"){
                    $productCollection->addAttributeToFilter(
                        [
                            ['attribute'=>'size_in_kg','eq'=> $size] // size filter
                        ]
                    );
                }
                else if($product['type_id'] == "grouped"){
                    $productRepo =$this->productRepository->getById($product['entity_id']);
                    $groupedProducts = $this->getGroupedProduct($product['entity_id']);
                    foreach($groupedProducts as $groupedProduct){
                        $sizeValue = $groupedProduct['size_in_kg_value'];
                        if($size == $sizeValue){
                            $groupedProductPrice = $this->getGroupedProductPrice($productRepo);
                            $groupedProductfinalPrice[] = $groupedProductPrice['starting_from_price'];
                            $groupedProductfinalPrice[] = $groupedProductPrice['starting_to_price'];
                        }
                    }
                }
            }
        }
        $finalPrice = [];
        foreach($productCollection->getData() as $price){
            $finalPrice[] = $this->productHelper->getFormattedPrice($price['final_price']);
        }
        $overAllPrice = array_merge($finalPrice, $groupedProductfinalPrice);
        if(!empty($overAllPrice)){
            $maxPrice = max($overAllPrice);
            $minPrice = min($overAllPrice);  
        }
        $prices['lowest_price'] = $minPrice;
        $prices['highest_price'] = $maxPrice;
        return $prices;
    }
    // get weight filter in tags
    public function getWeightTagFilter($productDataCollections, $tagFilter){
        $weights['value'] = "";
        $weights['display'] = "";
        $weight = [];
        $weightKg = [];
        $weightKgs = [];
        $weightArray = [];
        $weightArrays['size_in_kg'] = [];
        // get root category id
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $categoryIds = [];
        // Get data product collection
        foreach($productDataCollections as $productData){
            // Get product data from product repository
            $_product = $this->productRepository->getById($productData['entity_id']);
            $productId = $_product['entity_id'];
            $productType = $_product['type_id'];
            // For Simple Product
            if($productType == "simple"){
                $weights['value'] = $_product['size_in_kg'];
                $weights['display'] = $this->productHelper->getSizeAttributeValue($_product);
            }
            // For grouped Product
            if($productType == "grouped"){
                $groupedProducts = $this->getGroupedProduct($_product->getId());
                foreach($groupedProducts as $groupedProduct){
                    $childProductId = $groupedProduct['id'];
                    $childProduct = $this->productRepository->getById($childProductId);
                    $weight['value'] = $_product['size_in_kg'];
                    $weight['display'] = $this->productHelper->getSizeAttributeValue($childProduct);
                }
                $weightKg[] = $weight;
            }
            $weightKgs[] = $weights;
        }
        $weightArray = (array_merge($weightKgs, $weightKg));
        // weight layered filter
        $tempArray = array();
        foreach ($weightArray as $key => $value) {
            $age = $value["value"];
            if (isset($tempArray[$age])) {
                $tempArray[$age]["count"]++;
                unset($weightArray[$key]);
            } else {
                $tempArray[$age] = array(
                    "count" => 1
                );
            }
        }
        $weightArray = array_values($weightArray);
        foreach ($weightArray as &$value) {
            $age = $value["value"];
            $value["count"] = $tempArray[$age]["count"];
        }
        $weightFilters = [];
        // Removed empty value
        foreach($weightArray as $weight){
            if($weight['value'] != 0 && $weight['display'] != false){
                $weightFilter['value'] = $weight['value'];
                $weightFilter['display'] = $weight['display'];
                $weightFilter['count'] = $weight['count'];
                $weightFilters[] = $weightFilter;
            }
        }
        $weightArrays['size_in_kg'] = $weightFilters;
        return $weightArrays;
    }
    // get Categories filter in tags
    public function getCategoryTagFilter($entityId, $productDataCollections, $tagFilter, $filterableCategoryId){
        $allCategries = [];
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $categoryIds = [];
        $cat = [];
        foreach($productDataCollections as $productData){
            $_product = $this->productRepository->getById($productData['entity_id']);
            $categoryIds[] = $_product->getCategoryIds();
        }
        $catIds = [];
        foreach($categoryIds as $categoryIds){
            foreach($categoryIds as $categoryId){
               $cat['category_id'] = $categoryId;
               $catIds[] = $cat;
            }
        }
        $subCategory = [];
        $child = [];
        $parent = [];
        $child1 = [];
        $subCategory1 = [];
        $flag = 0;
        $catUnique = "";
        $tempArray = array();
        foreach ($catIds as $key => $value) {
            $age = $value["category_id"];
            if (isset($tempArray[$age])) {
                $tempArray[$age]["count"]++;
                unset($catIds[$key]);
            } else {
                $tempArray[$age] = array(
                    "count" => 1
                );
            }
        }
        $catIds = array_values($catIds);
        foreach ($catIds as &$value) {
            $age = $value["category_id"];
            $value["count"] = $tempArray[$age]["count"];
        }
        if(!empty($entityId)){
            $categories = $this->categoryModelFactory->create()->load($entityId);
            $child["id"] = $categories->getId();
            $child["name"] = $categories->getName();
            $child["url_key"] = $categories->getUrlKey();
            $child["request_path"] = $this->productHelper->getCategoryRewriteUrl($categories->getId());
            foreach($catIds as $category){
                $categories1 = $this->categoryModelFactory->create()->load($category['category_id']);
                if($categories1->getParentId() == $entityId){
                    $child1["id"] = $categories1->getId();
                    $child1["name"] = $categories1->getName();
                    $child1["url_key"] = $categories1->getUrlKey();
                    $child1["request_path"] = $this->productHelper->getCategoryRewriteUrl($categories1->getId());
                    $child1['product_count'] = $category['count'];
                    array_push($subCategory1, $child1); 
                } 
                if($category['category_id'] == $entityId){
                    $catUnique = $category['count'];
                }
            }
            $child['product_count'] = $catUnique;
            $child['level2'] = $subCategory1;
            array_push($subCategory, $child);   
            $parent[] = $child;
            $allCategroy['level1'] = $parent;
            $allCategries[] = $allCategroy;
        }
        else{
            if($filterableCategoryId == ""){
                foreach($catIds as $category){
                    $categories1 = $this->categoryModelFactory->create()->load($category['category_id']);
                    if($categories1->getParentId() == $root){
                        $child1["id"] = $categories1->getId();
                        $child1["name"] = $categories1->getName();
                        $child1["url_key"] = $categories1->getUrlKey();
                        $child1["request_path"] = $this->productHelper->getCategoryRewriteUrl($categories1->getId());
                        $child1['product_count'] = $category['count'];
                        array_push($subCategory1, $child1); 
                    } 
                }
            }
            else{
                $categories = $this->categoryModelFactory->create()->load($filterableCategoryId);
                $childCategories = $this->getChildCategories($categories);
                $subcategories = $childCategories->getData();
                foreach($catIds as $categoryEntityId){
                    $catEntityId = $categoryEntityId['category_id'];
                    foreach ($subcategories as $category) {
                        $categories1 = $this->categoryModelFactory->create()->load($category['entity_id']);
                        if($categories1->getId() == $catEntityId){
                            $child1["id"] = $categories1->getId();
                            $child1["name"] = $categories1->getName();
                            $child1["url_key"] = $categories1->getUrlKey();
                            $child1["request_path"] = $this->productHelper->getCategoryRewriteUrl($categories1->getId());
                            $child1['product_count'] = $categoryEntityId['count'];
                            array_push($subCategory1, $child1); 
                        }
                    } 
                }
            }
            $allCategroy['level1'] = $subCategory1;
            $allCategries[] = $allCategroy;
        }
        return $allCategries;
    }
     // Get tag price Filter
    public function getTagPriceFilter($productDataCollections, $tagFilter){
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $offerData = [];
        $newarrivalData = [];
        $response = [];
        $offer = 0;
        $newArrival = 0;
        $products = [];
        $offeredProductId = [];
        $arrivedProductId = [];
        foreach($productDataCollections as $productData){
            $offeredProductId['entity_id'] = "";
            $arrivedProductId['entity_id'] = "";
            $_product = $this->productRepository->getById($productData['entity_id']);
            if($_product->getTypeId() == "simple"){
                $onOffer = $this->getSpecialPriceByProductId($_product->getId());
                if($onOffer['tag'] == "On Offer" && $tagFilter == 1){
                    $offeredProductId['entity_id'] = $_product->getId();
                    $offeredProductId['special_price'] = $_product['special_price'];
                    $offer++;
                }
                else if($onOffer['tag'] == "On Offer" && $tagFilter != 2){
                    $offeredProductId['entity_id'] = $_product->getId();
                    $offeredProductId['special_price'] = $_product['special_price'];
                    $offer++;
                }
                $arrival = $this->getNewFromToDate($_product->getId());
                if($arrival['tag'] == "New Arrival" && $tagFilter == 2){
                    $arrivedProductId['entity_id'] = $_product->getId();
                    $arrivedProductId['special_price'] = $_product['special_price'];
                    $newArrival++;
                }
                else if($arrival['tag'] == "New Arrival" && $tagFilter != 1){
                    $arrivedProductId['entity_id'] = $_product->getId();
                    $arrivedProductId['special_price'] = $_product['special_price'];
                    $newArrival++;
                }
            }
            if($_product->getTypeId() == "grouped"){
                $flag = 0;
                $arrivalFlag = 0;
                $groupedProducts = $this->getGroupedProduct($_product->getId());
                $offeredProductIds = [];
                $arrivedProductIds = [];
                foreach($groupedProducts as $groupedProduct){
                    $onOffer = $this->getSpecialPriceByProductId($groupedProduct['id']);
                    if($onOffer['tag'] == "On Offer" && $tagFilter == 1){
                        $offeredProductIds['entity_id'] = $_product->getId();
                        $offeredProductIds['special_price'] = $_product['special_price'];
                        $flag = 1;
                    }
                    else if($onOffer['tag'] == "On Offer" && $tagFilter != 2){
                        $offeredProductIds['entity_id'] = $_product->getId();
                        $offeredProductIds['special_price'] = $_product['special_price'];
                        $flag = 1;
                    }
                    $arrival = $this->getNewFromToDate($groupedProduct['id']);
                    if($arrival['tag'] == "New Arrival" && $tagFilter == 2){
                        $arrivedProductIds['entity_id'] = $_product->getId();
                        $arrivedProductIds['special_price'] = $_product['special_price'];
                        $arrivalFlag = 1;
                    }
                    else if($arrival['tag'] == "New Arrival" && $tagFilter != 1){
                        $arrivedProductIds['entity_id'] = $_product->getId();
                        $arrivedProductIds['special_price'] = $_product['special_price'];
                        $arrivalFlag = 1;
                    }
                }
                if($flag == 1){
                    $offer++;
                }
                if($arrivalFlag == 1){
                    $newArrival++;
                }
            }
            if(!empty($offeredProductId['entity_id'])){
                $products[] = $offeredProductId;
            }
            if(!empty($arrivedProductId['entity_id'])){
                $products[] = $arrivedProductId;
            }
            if(!empty($offeredProductIds['entity_id'])){
                $products[] = $offeredProductIds;
            }
            if(!empty($arrivedProductIds['entity_id'])){
                $products[] = $arrivedProductIds;
            }
        }
        $response['products'] = $products;
        return $response;
    }

    // Get tag Filter
    public function getTagFilter($productDataCollections, $tagFilter){
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $offerData = [];
        $newarrivalData = [];
        $response = [];
        $offer = 0;
        $newArrival = 0;
        $products = [];
        $offeredProductId = [];
        $arrivedProductId = [];
        $offeredProductIds = [];
        $arrivedProductIds = [];
        foreach($productDataCollections as $productData){
            $offeredProductId['entity_id'] = "";
            $arrivedProductId['entity_id'] = "";
            $_product = $this->productRepository->getById($productData['entity_id']);
            if($_product->getTypeId() == "simple"){
                $onOffer = $this->getSpecialPriceByProductId($_product->getId());
                if($onOffer['tag'] == "On Offer"){
                    $offeredProductId['entity_id'] = $_product->getId();
                    $offeredProductId['special_price'] = $_product['special_price'];
                    $offer++;
                }
                else if($onOffer['tag'] == "On Offer"){
                    $offeredProductId['entity_id'] = $_product->getId();
                    $offeredProductId['special_price'] = $_product['special_price'];
                    $offer++;
                }
                $arrival = $this->getNewFromToDate($_product->getId());
                if($arrival['tag'] == "New Arrival"){
                    $arrivedProductId['entity_id'] = $_product->getId();
                    $arrivedProductId['special_price'] = $_product['special_price'];
                    $newArrival++;
                }
                else if($arrival['tag'] == "New Arrival"){
                    $arrivedProductId['entity_id'] = $_product->getId();
                    $arrivedProductId['special_price'] = $_product['special_price'];
                    $newArrival++;
                }
            }
            if($_product->getTypeId() == "grouped"){
                $flag = 0;
                $arrivalFlag = 0;
                $groupedProducts = $this->getGroupedProduct($_product->getId());
                foreach($groupedProducts as $groupedProduct){
                    $onOffer = $this->getSpecialPriceByProductId($groupedProduct['id']);
                    if($onOffer['tag'] == "On Offer"){
                        $offeredProductIds['entity_id'] = $_product->getId();
                        $offeredProductIds['special_price'] = $_product['special_price'];
                        $flag = 1;
                    }
                    else if($onOffer['tag'] == "On Offer"){
                        $offeredProductIds['entity_id'] = $_product->getId();
                        $offeredProductIds['special_price'] = $_product['special_price'];
                        $flag = 1;
                    }
                    $arrival = $this->getNewFromToDate($groupedProduct['id']);
                    if($arrival['tag'] == "New Arrival"){
                        $arrivedProductIds['entity_id'] = $_product->getId();
                        $arrivedProductIds['special_price'] = $_product['special_price'];
                        $arrivalFlag = 1;
                    }
                    else if($arrival['tag'] == "New Arrival"){
                        $arrivedProductIds['entity_id'] = $_product->getId();
                        $arrivedProductIds['special_price'] = $_product['special_price'];
                        $arrivalFlag = 1;
                    }
                }
                if($flag == 1){
                    $offer++;
                }
                if($arrivalFlag == 1){
                    $newArrival++;
                }
            }
            if(!empty($offeredProductId['entity_id'])){
                $products[] = $offeredProductId;
            }
            if(!empty($arrivedProductId['entity_id'])){
                $products[] = $arrivedProductId;
            }
            if(!empty($offeredProductIds['entity_id'])){
                $products[] = $offeredProductIds;
            }
            if(!empty($arrivedProductIds['entity_id'])){
                $products[] = $arrivedProductIds;
            }
        }
        if($offer != 0){
            $offerData['label'] = "On Offer";
            $offerData['count'] = $offer;
            $response['on_offer'] = $offerData;
        }
        if($newArrival != 0){
            $arrivalData['label'] = "New Arrival";
            $arrivalData['count'] = $newArrival;
            $response['new_arrival'] =  $arrivalData;
        }
        $response['products'] = $products;
        return $response;
    }
    
    // get specific categories in layered filter
    public function getSubCategoriesById($max, $min, $categoryId, $size, $productInCategory){
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $categoryIds = [];
        $mainCategory = [];
        $subCategory = [];
        $allCategries = [];
        $mainCategories = [];
        $categories = $this->categoryModelFactory->create()->load($categoryId);
        $filterArray = [];
        $filterableAttributes = $this->filterableAttributes;
        $appState = $this->state;
        $layerResolver = $this->layerResolver;
        $filterList = $this->filterListFactory->create(['filterableAttributes' => $filterableAttributes]);
        $layer = $layerResolver->get();
        $filters = $filterList->getFilters($layer);
        $layer->setCurrentCategory($categoryId);
        $productCollection = $layer->getProductCollection()
            ->addAttributeToFilter("visibility", ["neq" => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter("status", ["eq" => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED]);
        if(!empty($max) && (!empty($min))){
            $productCollection->addAttributeToSelect('*')->addFinalPrice();
            $productCollection->getSelect()->where("price_index.final_price > ".$min)
                    ->where("price_index.final_price < ".$max);
        }
        if($size != 0 && $size != ""){
            $productCollection->addAttributeToFilter(
                [
                    ['attribute'=>'size_in_kg','eq'=> $size] // size filter
                ]
            );
        }
        $maxPrice = $layer->getProductCollection()->getMaxPrice();
        $minPrice = $layer->getProductCollection()->getMinPrice();  
        $i = 0;
        foreach($filters as $filter)
        {
            $availablefilter = (string)$filter->getName(); 
            $items = $filter->getItems();
            $filterValues = array();
            $j = 0;
            foreach($items as $item)
            {
                $filterValues[$j]['display'] = strip_tags($item->getLabel());
                $filterValues[$j]['value']   = $item->getValue();
                $filterValues[$j]['count']   = $item->getCount(); 
                $j++;
            }
            if($availablefilter == "Category"){
                if(!empty($filterValues) || count($filterValues)>1)
                {
                    $filterArray =  $filterValues;
                }
            }
            $i++;
        } 
        $categoryData = $this->categoryModelFactory->create()->load($categoryId);
        if($categoryId == $root){
            $categoryDatas = $this->categoryModelFactory->create()->load($categoryData->getParentId());
            $mainCategory['id'] = $categoryData->getId();
            $mainCategory['name'] = $categoryData->getName();
            $mainCategory['url_key'] = $categoryData->getUrlKey();
            $mainCategory["request_path"] = $this->productHelper->getCategoryRewriteUrl($categoryData->getId());
            $mainCategory['product_count'] = $this->getProductCollectionCount($categoryData->getId());
            $mainCategory['bulk_order'] = $categoryData->getBulkOrder();
            foreach ($filterArray as $filterCategoryArray) {
                $categories1 = $this->categoryModelFactory->create()->load($filterCategoryArray['value']);
                    if($filterCategoryArray != 0){
                        $child["id"] = $categories1->getId();
                        $child["name"] = $categories1->getName();
                        $child["url_key"] = $categories1->getUrlKey();
                        $child["request_path"] = $categories1['request_path'];
                        $child['product_count'] = $filterCategoryArray['count'];
                        $child['bulk_order'] = $categories1->getBulkOrder();
                        array_push($subCategory, $child);   
                    }
            }
            $mainCategory['level2'] = $subCategory;
            $mainCategories[] = $mainCategory;
            $allCategroy['level1'] = $mainCategories;
            $allCategries[] = $allCategroy;
            return $allCategries;
        }
        else{
            $categoryDatas = $this->categoryModelFactory->create()->load($categoryData->getParentId());
            $mainCategory['id'] = $categoryData->getId();
            $mainCategory['name'] = $categoryData->getName();
            $mainCategory['url_key'] = $categoryData->getUrlKey();
            $mainCategory["request_path"] = $this->productHelper->getCategoryRewriteUrl($categoryData->getId());
            $mainCategory['product_count'] = $productInCategory;
            $mainCategory['bulk_order'] = $categoryData->getBulkOrder();
            foreach ($filterArray as $filterCategoryArray) {
                $categories1 = $this->categoryModelFactory->create()->load($filterCategoryArray['value']);
                    if($filterCategoryArray != 0){
                        $child["id"] = $categories1->getId();
                        $child["name"] = $categories1->getName();
                        $child["url_key"] = $categories1->getUrlKey();
                        $child["request_path"] = $this->productHelper->getCategoryRewriteUrl($categories1->getId());
                        $child['product_count'] = $filterCategoryArray['count'];
                        $child['bulk_order'] = $categories1->getBulkOrder();
                        array_push($subCategory, $child);   
                    }
            }
            $mainCategory['level2'] = $subCategory;
            $mainCategories[] = $mainCategory;
            $allCategroy['level1'] = $mainCategories;
            $allCategries[] = $allCategroy;
            return $allCategries;
        }
    }
        
    /**
     * Get Searched Categories List
     * @return array
     */
    public function getSearchCategories($products){
        $categortSearchFilter = [];
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $rootNodeId = $this->storeManager->getStore($storeId)->getRootCategoryId();
        foreach($products as $product){
            $productCategory = $this->productFactory->create()->load($product['entity_id']);
            $categoryIds[] = $productCategory->getCategoryIds();
        }
        foreach($categoryIds as $cateories){
            foreach($cateories as $categoryArray){
                $searchCategoryList[] = $categoryArray;
            }
        }
        $searchArray1 = [];
        $searchArray2 = [];
        $searchLayeredFilter = [];
        $categorySearchFilter = [];
        foreach($searchCategoryList as $searchCategory){
            $parCategory = $this->categoryFactory->load($searchCategory);
            if($rootNodeId == $parCategory['parent_id']){
                $searchArray1[] = $parCategory['entity_id'];
            }
            $parCategories = $this->categoryFactory->load($searchCategory);
            if($rootNodeId != $parCategories['parent_id']){
                $searchArray2[] = $parCategories['parent_id'];
            }
        }
        $categorySearchFilter = array_merge($searchArray1, $searchArray2);
        $productInCategroyCount = count($categoryIds);
        $parentCategoryIds = array_unique($categorySearchFilter);
        foreach($parentCategoryIds as $parentCategoryId){
            $categoryName = $this->getCategoryNameById($parentCategoryId, $storeId);
            $categoryFilter['category_id'] = $parentCategoryId;
            $categoryFilter['category_name'] = $categoryName;
            $categoryFilter['product_count'] = $productInCategroyCount;
            $categoryFilter['url_key'] = $this->productHelper->getCategoryRewriteUrl($parentCategoryId);
            $searchLayeredFilter[] = $categoryFilter;
        }
        return $searchLayeredFilter;
    }

    public function getCategoryNameById($id, $storeId)
    {
        $categoryName = "";
        if(!empty($categoryName)){
            $categoryInstance = $this->categoryRepository->get($id, $storeId);
            $categoryName = $categoryInstance->getName();
        }
        return $categoryName;
    }

    /**
     * Get All Categories List
     * @return array
     */
    public function getAllCategories(){
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $categoryId = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $allCategroy = [];
        $mainCategory = [];
        $subCategory = [];
        $categories = $this->categoryModelFactory->create()->load($categoryId);
        $mainCategory['id'] = $categories->getId();
        $mainCategory['name'] = $categories->getName();
        $mainCategory['include_in_menu'] = round($categories->getIncludeInMenu());
        $mainCategory["image"] = "";
        if($categories->getImage() != Null){
            $mainCategory["image"] = $this->urlHelper->getBaseUrl() . $categories->getImage();
        }
        $mainCategory['url_key'] = $categories->getUrlKey();
        $mainCategory['product_count'] = $this->getProductCollectionCount($categories->getId());
        $allCategroy["root"] = $mainCategory;
        $childCategories = $this->getChildCategories($categories);
        $subcategories = $childCategories->getData();
        foreach ($subcategories as $category) {
            $child = [];
            $subCategory1 = [];
            $categories1 = $this->categoryModelFactory->create()->load($category['entity_id']);
            // checking include in menu and active 
            if($categories1['is_active'] == self::ENABLED){
                $child["id"] = $categories1->getId();
                $child["name"] = $categories1->getName();
                $child['include_in_menu'] = round($categories1->getIncludeInMenu());
                $child["image"] = "";
                if($categories1->getImage() != Null){
                    $child["image"] = $this->urlHelper->getBaseUrl() . $categories1->getImage();
                }
                $child["url_key"] = $categories1->getUrlKey();
                $child["request_path"] = $category['request_path'];
                $child['product_count'] = $this->getProductCollectionCount($categories1->getId());
                $subcategories1 = $this->getChildCategories($categories1);
                foreach (($subcategories1->getData()) as $category1) {
                    $child1 = [];
                    $subCategory2 = [];
                    $categories2 = $this->categoryModelFactory->create()->load($category1['entity_id']);
                    if($categories2['is_active'] == self::ENABLED){
                        $child1["id"] = $categories2->getId();
                        $child1["name"] = $categories2->getName();
                        $child1['include_in_menu'] = round($categories2->getIncludeInMenu());
                        $child1["image"] = "";
                        if($categories2->getImage() != null){
                            $child1["image"] = $this->urlHelper->getBaseUrl() . $categories2->getImage();
                        }
                        $child1["url_key"] = $categories2->getUrlKey();
                        $child1["request_path"] = $category1['request_path'];
                        $child1['product_count'] = $this->getProductCollectionCount($categories2->getId());
                        $subCategory1[] = $child1;
                    }
                }
                $child["level2"] = $subCategory1;
                array_push($subCategory, $child);
                }   
            }
        $mainCategory['level1'] = $subCategory;
        $allCategroy["root"] = $mainCategory;
        return $allCategroy;  
    }
    // Get Search result page layered filter navigation 
    public function getSearchResultLayeredFilter($max, $min, $keyword, $size, $filterableCategoryId){
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $categoryIds = [];
        $maxPrice = "";
        $minPrice = "";
        $mainCategory = [];
        $subCategory = [];
        $allCategries = [];
        $mainCategories = [];
        $filterArray = [];
        $weightFilterArray = [];
        $filterableAttributes = $this->filterableAttributes;
        $appState = $this->state;
        $layerResolver = $this->layerResolver;
        $filterList = $this->filterListFactory->create(['filterableAttributes' => $filterableAttributes]);
        $layer = $layerResolver->get();
        $filters = $filterList->getFilters($layer);
        if($filterableCategoryId != "" && $filterableCategoryId != 0){
            $layer->setCurrentCategory($filterableCategoryId);
        }
        $productCollection = $layer->getProductCollection()
            ->addAttributeToFilter("visibility", ["neq" => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter("status", ["eq" => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED]);
        $searchTerm = trim($keyword);
        $searchTerms = explode(' ', $searchTerm);
        if($keyword != ""){
            $productCollection
                ->addAttributeToSelect('*');
            $productCollection->addAttributeToFilter(
                [
                    ['attribute' => 'name',
                     'regexp' => implode('|', array_map(function($term) {
                        return '(^|\s)' . preg_quote($term) . '(\s|$)';
                     }, $searchTerms))],
                    ['attribute' => 'name', 'like' => '%' . $searchTerm . '%'],
                    ['attribute' => 'sku', 'like' => '%' . $searchTerm . '%'],
                    ['attribute' => 'description', 'null' => true, 'like' => '%' . $searchTerm . '%'],
                ]
            )->addAttributeToFilter('sku', ['like' => '%' . $searchTerm . '%']);
        }
        if(!empty($max) && (!empty($min))){
            $productCollection->addAttributeToSelect('*')->addFinalPrice();
            $productCollection->getSelect()->where("price_index.final_price > ".$min)
                  ->where("price_index.final_price < ".$max);
        }
        if($size != 0 && $size != ""){
            $productCollection->addAttributeToFilter(
                [
                    ['attribute'=>'size_in_kg','eq'=> $size] // size filter
                ]
            );
        }
        $maxPrice = $layer->getProductCollection()->getMaxPrice();
        $minPrice = $layer->getProductCollection()->getMinPrice();  
        $i = 0;
        foreach($filters as $filter)
        {
            // get product atrribute label
            $availablefilter = (string)$filter->getName(); 
            // get product atrribute label
            $attributeCode  = (string) $filter->getRequestVar();
            $items = $filter->getItems();
            $filterValues = array();
            $j = 0;
            foreach($items as $item)
            {
                $filterValues[$j]['display'] = strip_tags($item->getLabel());
                $filterValues[$j]['value']   = $item->getValue();
                $filterValues[$j]['count']   = $item->getCount(); 
                $j++;
            }
            if(!empty($filterValues) || count($filterValues)>1)
            {
                $filterArray[$attributeCode] =  $filterValues;
            }
            $i++;
        } 
        $filterArray['max_price'] = $maxPrice;
        $filterArray['min_price'] = $minPrice;
        return $filterArray;
    }

    // Get Searchable weight list 

    public function getSearchableWeight($maxPrice, $minPrice, $keyword, $size, $filterableCategoryId){
        $filterableArray['size_in_kg'] = [];
        $weightFilter = $this->getSearchResultLayeredFilter($maxPrice, $minPrice, $keyword, $size, $filterableCategoryId);
        if(!empty($weightFilter['size_in_kg'])){
            $filterableArray['size_in_kg'] = $weightFilter['size_in_kg'];
        }
        return $filterableArray;
    }
    /**
     * Get All Categories List
     * @return array
     */
    public function getSearchResultCategories($maxPrice, $minPrice, $keyword, $size, $filterableCategoryId){
        $subCategory = [];
        $filterArray = $this->getSearchResultLayeredFilter($maxPrice, $minPrice, $keyword, $size, $filterableCategoryId);
        if(!empty($filterArray['cat'])){   
            foreach ($filterArray['cat'] as $filterMainCategoryArray) {
                $child = [];
                $subCategory1 = [];
                $categories1 = $this->categoryModelFactory->create()->load($filterMainCategoryArray['value']);
                $child["id"] = $categories1->getId();
                $child["name"] = $categories1->getName();
                $child["url_key"] = $categories1->getUrlKey();
                $child["request_path"] = $this->productHelper->getCategoryRewriteUrl($categories1->getId());
                $child['product_count'] = $filterMainCategoryArray['count'];
                array_push($subCategory, $child);
            }   
        }
        $mainCategory['level1'] = $subCategory;
        return $mainCategory; 
    }

    // Get maximum and minimum price in searchable filter 
    public function getSearchableMinMaxPrice($maxPrice, $minPrice, $keyword, $size, $filterableCategoryId){
        $priceFilter = [];
        $filterArray = $this->getSearchResultLayeredFilter($maxPrice, $minPrice, $keyword, $size, $filterableCategoryId);
        $priceFilter['min_price'] = $filterArray['min_price'];
        $priceFilter['max_price'] = $filterArray['max_price'];
        return $priceFilter;
    }
    public function getChildCategories($categories){
        $childCategories = $this->categoryCollectionFactory->create();
        $childCategories->addAttributeToSelect(
            'url_key'
        )->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'all_children'
        )->addAttributeToSelect(
            'is_anchor'
        )->addAttributeToFilter(
            'is_active',
            1
        )->addIdFilter(
            $categories->getChildren()
        )->setOrder(
            'name',
            \Magento\Framework\DB\Select::SQL_ASC
        )->setOrder(
            'position',
            \Magento\Framework\DB\Select::SQL_ASC
        )->joinUrlRewrite();
        return $childCategories;
    }
    // Get Related Product by id
    public function getRelatedProductsList($productId)
    {
        $i = 0;
        $relatedProductData = [];
        $relatedProduct = [];
        $sizeInKg = "";
        $productName = "";
         try {
            $mediaUrl = $this->urlHelper->getMediaUrl();
            $product = $this->productRepository->getById($productId);
            $related = $product->getRelatedProducts();
            if (count($related) > 0) {
                foreach ($related as $item) {
                    $relatedProductItem = $item->getData();
                    if(!empty($relatedProductItem)){
                        $productData = $this->productFactory->create()->load($relatedProductItem['entity_id'],'entity_id');
                        $productDatas = $productData->getData();
                        if(!empty($productDatas) && $productDatas['status'] == self::ENABLED && $productDatas['visibility'] != self::VISIBILITY){
                            $_product = $this->productRepository->getById($productDatas['entity_id']);
                            if(!empty($this->productHelper->getSizeAttributeValue($productData))){
                                $sizeInKg = $this->productHelper->getSizeAttributeValue($productData);
                            }
                            // Get Product Name
                            if(isset($productDatas['name'])){
                                $productName = $productDatas['name']; 
                            }
                            // On offer / New Arrival tag
                            $newDate = $this->getNewFromToDate($productDatas['entity_id']);
                            $specialPriceByProductId = $this->getSpecialPriceByProductId($productDatas['entity_id']);
                            $tag['new_arrival'] = $newDate['tag']; 
                            $tag['on_offer'] = $specialPriceByProductId['tag']; 
                            $specialPrice = "";
                            $productPrice ="";
                            $showSpecialPrice = "";
                            $showProductPrice ="";
                            $startingFromPrice = "";
                            $startingFromShowPrice = "";
                            $startingToPrice = "";
                            $startingToShowPrice = "";
                            $qty = $this->productHelper->getSalableQuantity($productDatas['sku']);
                            if($productDatas['type_id'] == "grouped"){
                                $groupedProduct = $this->getGroupedProductPrice($productData);
                                $startingFromPrice = $groupedProduct['starting_from_price'];
                                $startingFromShowPrice = $groupedProduct['starting_from_display_price'];
                                $startingToPrice = $groupedProduct['starting_to_price'];
                                $startingToShowPrice = $groupedProduct['starting_to_display_price'];
                                $tag['new_arrival'] = $groupedProduct['tags']['new_arrival'];
                                $tag['on_offer'] = $groupedProduct['tags']['on_offer'];
                                $childrenCount = $groupedProduct['children_count'];
                                // Get Stock Status
                                $_children = $productData->getTypeInstance(true)->getAssociatedProducts($productData);
                                $count = 0;
                                foreach($_children as $child){
                                    $childItemStockStatus = $child->getId();
                                    $isInstockStatus = $this->getStockStatus($child->getId());
                                    $groupedQty = $this->productHelper->getSalableQuantity($child->getSku());
                                    if ($groupedQty > 0 && $child->getId() != $productData->getId() && $child->getStatus() == self::ENABLED && $isInstockStatus == self::ISINSTOCK) {
                                        $count++;
                                    }
                                }
                                if($productDatas['quantity_and_stock_status']['is_in_stock'] == true && $count != 0){
                                    $stockStatus = "In stock";
                                }
                                else{
                                    $stockStatus = "Out of stock";
                                }
                            }
                            else if($productDatas['type_id'] == "simple"){
                                $specialPriceByProductId = $this->getSpecialPriceByProductId($productDatas['entity_id']);
                                $specialPrice = $this->productHelper->getFormattedPrice($specialPriceByProductId['special_price']);
                                $showSpecialPrice = $this->productHelper->INDMoneyFormat($specialPriceByProductId['special_price']);
                                $productPrice =  $this->productHelper->getFormattedPrice($productDatas['price']);
                                $showProductPrice =  $this->productHelper->INDMoneyFormat($productDatas['price']);
                                 // Get Stock Status
                                if($qty > 0){
                                    $stockStatus = "In stock";
                                }
                                else{
                                    $stockStatus = "Out of stock";
                                }
                            }
                            $newArrivalOnOfferTags = "";
                            if(!empty($tag)){
                                $newArrivalOnOfferTags = $tag;
                            }
                            // Product Color
                            // Get Product Color Atrribute Value
                            $color = $productData->getResource()->getAttribute(CmsPlpPdp::ATTRIBUTE_CODE)->getFrontend()->getValue($productData);
                            // Product Short Description
                            $shortDesc = "";
                            if(isset($productDatas['short_description'])){
                                $shortDesc = $productDatas['short_description'];
                            }
                            //Get Product Type
                            $productType = "";
                            if($productDatas['type_id'] != ""){
                                $productType = $productDatas['type_id'];
                            }
                            $productRewriteUrl = $this->productHelper->getProductRewriteUrl($productDatas['entity_id']);
                            $relatedProductDatas['id'] = $productDatas['entity_id'];
                            $relatedProductDatas['type'] = $productType;
                            $relatedProductDatas['name'] = $productName;
                            $relatedProductDatas['sku'] = $productDatas['sku'];
                            $relatedProductDatas['short_description'] = $shortDesc;
                            $relatedProductDatas['price'] = $productPrice;
                            $relatedProductDatas['starting_rom_price'] = $startingFromPrice;
                            $relatedProductDatas['starting_from_display_price'] = $startingFromShowPrice;
                            $relatedProductDatas['starting_to_price'] = $startingToPrice;
                            $relatedProductDatas['starting_to_display_price'] = $startingToShowPrice;
                            $relatedProductDatas['display_price'] = $showProductPrice;
                            $relatedProductDatas['special_price'] = $specialPrice;
                            $relatedProductDatas['display_special_price'] = $showSpecialPrice;
                            $relatedProductDatas['tag'] = $newArrivalOnOfferTags;
                            $relatedProductDatas['color'] = $color;
                            $relatedProductDatas['size_in_kg'] = $sizeInKg;
                            $relatedProductDatas['stock_status'] = $stockStatus;
                            $relatedProductDatas['min_qty'] = $this->getMinSaleQtyById($productDatas['entity_id']);
                            $relatedProductDatas['qty'] = $qty;
                            $relatedProductDatas['url_key'] = $productRewriteUrl;
                            if($productType == "grouped"){
                                $relatedProductDatas['children_count'] = $childrenCount;
                            }
                            $relatedProductDatas['media_gallery'] = $this->GetMediaGalleryImage($productDatas);
                            // Grouped Product
                            $relatedProductDatas['grouped_product'] = [];
                            if($productDatas['type_id'] == "grouped"){
                                $groupedProducts = $this->getGroupedProduct($productDatas['entity_id']);
                                $relatedProductDatas['grouped_product'] = $groupedProducts;
                            }
                            $relatedProductData[] = $relatedProductDatas;
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            $relatedProductData = $exception;
        }
        
        return $relatedProductData;
    }
    public function getProductbySku($sku, $productEntityId){
        $now = $this->timezoneInterface->date()->format('Y-m-d 00:00:00');
        $productData = [];
        $sku = trim($sku);
        $product  = $this->productRepository->get($sku);
        $productId = $product->getId();
        $enabled = $product->getStatus();
        $visibility = $product->getVisibility();
        $productType= $product->getTypeID();
        $mediaUrl = $this->urlHelper->getMediaUrl();
        if($enabled == 1 && $productId != "" && $visibility != self::VISIBILITY && $productId != $productEntityId){
            $productData['product_id'] = $product->getId();
            $productData['name'] = $product->getName();
            $productData['sku'] = $product->getSku();
            $productData['product_type'] = $product->getTypeId();
            $productData['url'] = $this->categoryHelper->getProductUrlKey($product->getProductUrl());
            if(isset($product['main_image_s3_url']) && $product['main_image_s3_url'] != null && (!empty($product['main_image_s3_url']))){
                $productData['image'] = $product['main_image_s3_url'];
            }else{
                $productData['image'] = $this->urlHelper->getPlaceHolderImage();
            }
            $tag = [];
            $newArrival = $this->getNewFromToDate($product->getId());
            $tag['new_arrival'] = $newArrival['tag'];
            $tag['on_offer'] = "";
            $productData['starting_from_price'] = "";
            $productData['display_starting_from_price'] = "";
            $productData['starting_to_price'] = "";
            $productData['display_starting_to_price'] = "";
            $productData['price'] =  "";
            $productData['display_price'] = "";  
            $productData['special_price'] = "";
            $productData['display_special_price'] = "";
            $productDataFactory = $this->productFactory->create()->load($product->getId());
            if($product['type_id'] == "simple"){
                $specialPriceByProductId = $this->getSpecialPriceByProductId($product['entity_id']);
                $tag['on_offer'] = $specialPriceByProductId['tag'];
                $productData['special_price'] = $this->productHelper->getFormattedPrice($specialPriceByProductId['special_price']);
                $productData['display_special_price'] = $this->productHelper->INDMoneyFormat($specialPriceByProductId['special_price']);
                $productData['price'] =  $this->productHelper->getFormattedPrice($product['price']);
                $productData['display_price'] =  $this->productHelper->INDMoneyFormat($product['price']);                                  
            }
            else if($product['type_id'] == "grouped"){
                $groupedProducts = $this->getGroupedProductPrice($productDataFactory);
                $productData['starting_from_price'] = $groupedProducts['starting_from_price'];
                $productData['display_starting_from_price'] = $groupedProducts['starting_from_display_price'];
                $productData['starting_to_price'] = $groupedProducts['starting_to_price'];
                $productData['display_starting_to_price'] = $groupedProducts['starting_to_display_price'];
                $tag['new_arrival'] = $groupedProducts['tags']['new_arrival'];
                $tag['on_offer'] = $groupedProducts['tags']['on_offer'];
                $productData['children_count'] = $groupedProducts['children_count'];
            }   
            $productData['tags'] = $tag;                          
            
            return $productData;
        }  
    }
    // get same catgory products
    public function getSameCatgoryProducts($sameCategoryId, $categoryIds, $productId){
        $productDatas = [];
        if($sameCategoryId != ""){
            $categories = $this->categoryModelFactory->create()->load($sameCategoryId);
            // get the details of category
            $productDataCollection = $categories->getProductCollection();
            $productDataCollection->addAttributeToSelect('sku'); 
            // status Filter
            $productDataCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); // Filter enable product
            // // Visibility Filter
            $productDataCollection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)); 
            $productDataCollection->setPageSize($this->urlHelper->getSameCategoryLimit());
            $productCollections = $productDataCollection->getData();
            $productCount = count($productDataCollection);
            if(!empty($productCollections)){
                foreach($productCollections as $productCollection){
                    if($productCollection['status'] == CmsPlpPdp::ENABLED && $productCollection['visibility'] != CmsPlpPdp::VISIBILITY){
                        if($this->getProductbySku($productCollection['sku'], $productId) != Null && (!empty( $this->getProductbySku($productCollection['sku'], $productId)))){
                            $productDatas[] = $this->getProductbySku($productCollection['sku'], $productId);
                        } 
                    }   
                }
            }
        }
        else{
            $categories = array('in' => $categoryIds);
            $productDataCollection = $this->productCollectionFactory->create();
            $productDataCollection->addAttributeToSelect('sku'); 
            // status Filter
            $productDataCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); // Filter enable product
            // Visibility Filter
            $productDataCollection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)); 
            $productDataCollection->addCategoriesFilter($categories);
            $productDataCollection->setPageSize($this->urlHelper->getSameCategoryLimit());
            $productCollections = $productDataCollection->getData();
            $productCount = count($productDataCollection);
            if(!empty($productCollections)){
                foreach($productCollections as $productCollection){
                    if($productCollection['status'] == CmsPlpPdp::ENABLED && $productCollection['visibility'] != CmsPlpPdp::VISIBILITY){
                        if($this->getProductbySku($productCollection['sku'], $productId) != Null && (!empty( $this->getProductbySku($productCollection['sku'], $productId)))){
                            $productDatas[] = $this->getProductbySku($productCollection['sku'], $productId);
                        } 
                    }   
                }
            }
        }
        return $productDatas;
    }
     // get all the category ids by product id
     public function getCategoryIds(int $productId)
     {
         $categoryIds = $this->productCategoryList->getCategoryIds($productId);
         $category = [];
         if ($categoryIds) {
             $category = array_unique($categoryIds);
         }
         $cateoryId = $category[0];
         return $cateoryId;
     }
    public function getSizeFilter($category){ 
        $filterableAttributes = $this->filterableAttributes;
        $appState = $this->state;
        $layerResolver = $this->layerResolver;
        $filterList = $this->filterListFactory->create(['filterableAttributes' => $filterableAttributes]);
            $layer = $layerResolver->get();
            $layer->setCurrentCategory($category);
            $filters = $filterList->getFilters($layer); 
        $i = 0;
       foreach($filters as $filter)
       {
           $availablefilter = $filter->getRequestVar(); 
           $items = $filter->getItems(); 
           $filterValues = array();
           $j = 0;
           foreach($items as $item)
           {
               $filterValues[$j]['display'] = strip_tags($item->getLabel());
               $filterValues[$j]['value']   = $item->getValue();
               $filterValues[$j]['count']   = $item->getCount(); 
               $j++;
           }
           if(!empty($filterValues) && count($filterValues)>1)
           {
               $filterArray[$availablefilter] =  $filterValues;
           }
           $i++;
       }  
       return $filterArray;
    }
    public function getGroupedProductPrice($productData){
        $groupedProductDatas = [];
        $arriva = [];
        $offer = [];
        $tag['new_arrival'] = "";
        $tag['on_offer'] = "";
        $specialPriceByProductId['tag'] = "";
        $groupedProductDatas['starting_from_price'] = "";
        $groupedProductDatas['starting_from_display_price'] = "";
        $groupedProductDatas['starting_to_price'] = "";
        $groupedProductDatas['starting_to_display_price'] = "";
        $arrival = [];
        $count = 0;
        $total = 0;
        $special = 0;
        $lowestprice = '';
        $childSpecialPrice = '';
        $childShowSpecialPrice = '';
        $prices = [];
        $usedProds = $productData->getTypeInstance(true)->getAssociatedProducts($productData);
        foreach ($usedProds as $child) {
            $childProduct = $this->productFactory->create()->load($child->getId())->getData();
            if ($count == 0){
                $lowestprice = $child->getPrice();
            }
            $specialPriceByProductId = $this->getSpecialPriceByProductId($childProduct['entity_id']);
            $newFromToDate = $this->getNewFromToDate($childProduct['entity_id']);
            $arrival[] = $newFromToDate['tag'];
            if($specialPriceByProductId['special_price'] != ""){
                $offer[] = $specialPriceByProductId['tag'];
                $childSpecialPrice = $this->productHelper->getFormattedPrice($specialPriceByProductId['special_price']);
                $childShowSpecialPrice = $this->productHelper->INDMoneyFormat($specialPriceByProductId['special_price']);
                $prices[] = $specialPriceByProductId['special_price'];
                $special++;
            }
            else{
                $prices[] = $child->getPrice();
            }
                $lowestprice = $child->getPrice();
            if($this->productHelper->getSalableQuantity($childProduct['sku']) > 0){
                $total++;
            }
            $count++;
        }
        foreach($arrival as $arrivalTag){
            if($arrivalTag == "New Arrival"){
                $tag['new_arrival'] = "New Arrival";
            }
        }
        foreach($offer as $offerderTag){
            if($offerderTag == "On Offer"){
                $tag['on_offer'] = "On Offer";
            }
        }
        if(!empty($prices)){
            $groupedProductDatas['starting_from_price'] = $this->productHelper->getFormattedPrice(min($prices));
            $groupedProductDatas['starting_from_display_price'] = $this->productHelper->INDMoneyFormat(min($prices));
            $groupedProductDatas['starting_to_price'] = $this->productHelper->getFormattedPrice(max($prices));
            $groupedProductDatas['starting_to_display_price'] = $this->productHelper->INDMoneyFormat(max($prices));
        }
        $newPrice = $specialPriceByProductId;
        if($newPrice['tag'] != ""){
            $tag['on_offer'] = $newPrice['tag']; 
        }
        $groupedProductDatas['tags'] = $tag;
        $groupedProductDatas['children_count'] = $total;
        return $groupedProductDatas;
    }
    public function GetMediaGalleryImage($productData){
        $productMediaGalleryImages = [];
        if(isset($productData['main_image_s3_url']) && $productData['main_image_s3_url'] != null && (!empty($productData['main_image_s3_url']))){
            $productMediaGalleryImages[] = $productData['main_image_s3_url'];
        }
        if(isset($productData['image1_s3_url']) && $productData['image1_s3_url'] != null && (!empty($productData['image1_s3_url']))){
            $productMediaGalleryImages[] = $productData['image1_s3_url'];
        }
        if(isset($productData['image2_s3_url']) && $productData['image2_s3_url'] != null && (!empty($productData['image2_s3_url']))){
            $productMediaGalleryImages[] = $productData['image2_s3_url'];
        }
        if(isset($productData['image3_s3_url']) && $productData['image3_s3_url'] != null && (!empty($productData['image3_s3_url']))){
            $productMediaGalleryImages[] = $productData['image3_s3_url'];
        }
        if(isset($productData['image4_s3_url']) && $productData['image4_s3_url'] != null && (!empty($productData['image4_s3_url']))){
            $productMediaGalleryImages[] = $productData['image4_s3_url'];
        }
        if(empty($productMediaGalleryImages)){
            $productMediaGalleryImages[] = $this->urlHelper->getPlaceHolderImage();
        }
        $productMediaGalleryImage['image'] = $productMediaGalleryImages;
        $productImages['image'] = $productMediaGalleryImages;
        return $productImages;
    }
}
