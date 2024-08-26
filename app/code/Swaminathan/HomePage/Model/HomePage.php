<?php
namespace Swaminathan\HomePage\Model;
use Swaminathan\HomePage\Api\HomePageInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Search\Model\Query;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory; 
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\CategoryManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterfaceFactory;
use Swaminathan\Testimonials\Model\Testimonial;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Swaminathan\HomePage\Helper\CategoryHelper;
use Swaminathan\HomePage\Helper\CurrencyHelper;
use Swaminathan\HomePage\Helper\ConstantsHelper;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Sparsh\Banner\Block\Banner;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Swaminathan\Offers\Model\ResourceModel\Offers\CollectionFactory as OfferCollection;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Directory\Model\Currency;
use Swaminathan\CmsPlpPdp\Model\CmsPlpPdp;
use Magento\Catalog\Model\ProductFactory;
use Magento\Cms\Model\PageRepository;
use Magento\UrlRewrite\Model\UrlFinderInterface;

class HomePage  extends \Magento\Framework\View\Element\Template implements HomePageInterface
{
    const MAX_MENU_CATEGORY = 6;

    const TILE3_PAGESIZE = 4;

    const TILE4_PAGESIZE = 4;

    const INCLUDEINMENU = 1;

    const PAGE_SIZE = 50;

    const PAGE_COUNT = 1;

    const TEMPLE_COLLECTION = 1;

    const TOP_COLLECTION = 1;

    const ABOUT_US_URL_KEY = "about-us";
    
    const META_TITLE = 'contactus/seo/meta_title';

    const META_KEYWORDS = 'contactus/seo/meta_keywords';

    const META_DESCRIPTION = 'contactus/seo/meta_description';


    protected $timezoneInterface;

    protected $currency;

    protected $productHelper;

    protected $cmsPlpPdp;

    protected $productFactory;

    protected $pageRepository;

    protected $urlFinderInterface;

    public function __construct(
        Context $context,
        BlockRepositoryInterface $blockRepository,
        Query $query,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CollectionFactory $categoryFactory,
        CategoryRepositoryInterface $categoryRepository,
        CategoryManagementInterface $categoryManagement,
        Testimonial $testimonial,
        UrlHelper $urlHelper,
        CategoryHelper $categoryHelper,
        ProductRepository $productRepository,
        Product $product,
        CategoryFactory $category,
        ProductCollection $productCollection,
        ProductRepositoryInterfaceFactory $productRepositoryFactory,
        Banner $banner,
        DateTime $dateTime,
        OfferCollection $offerCollection,
        CurrencyHelper $currencyHelper,
        JsonFactory $resultJsonFactory,
        EncoderInterface $jsonEncoder,
        LoggerInterface $logger,
        TimezoneInterface $timezoneInterface,
        Currency $currency,
        ProductHelper $productHelper,
        CmsPlpPdp $cmsPlpPdp,
        ProductFactory $productFactory,
        PageRepository $pageRepository,
        UrlFinderInterface $urlFinderInterface
    ){
        $this->blockRepository = $blockRepository;
        $this->query = $query;
        $this->scopeConfig= $scopeConfig;
        $this->storeManager = $storeManager;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryManagement = $categoryManagement;
        $this->testimonial =  $testimonial;
        $this->urlHelper = $urlHelper;
        $this->categoryHelper = $categoryHelper;
        $this->productRepository = $productRepository;
        $this->product = $product;
        $this->category =  $category;
        $this->productCollection =  $productCollection;
        $this->productRepositoryFactory = $productRepositoryFactory;
        $this->banner = $banner;
        $this->dateTime = $dateTime;
        $this->logger =  $logger;
        $this->offerCollection =  $offerCollection;
        $this->currencyHelper =  $currencyHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->jsonEncoder = $jsonEncoder;
        $this->timezoneInterface = $timezoneInterface;
        $this->currency = $currency;
        $this->productHelper = $productHelper;
        $this->cmsPlpPdp = $cmsPlpPdp;
        $this->productFactory = $productFactory;
        $this->pageRepository = $pageRepository;
        $this->urlFinderInterface = $urlFinderInterface;
        parent::__construct($context);
    }
 
    //get the system config value by the constant
    public function getConfigValue($config){
        return $this->scopeConfig->getValue($config,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,    ); 
    }

    /**
     * @return string[]
    */
    public function getHome()
    {
        $response[]= [
            'currency' => $this->getCurrency(),
            'metaTitle' => $this->getMetaTitle(),
            'metaKeywords' => $this->getMetaKeywords(),
            'metaDescription' => $this->getMetaDescription(),
            'banner' => $this->getBanners(),
            'categories' => $this->getAllCategories(),
            'trending' => $this->getTrending(),
            'offerproduct' => $this->getOfferProduct(),
            'newarrival' => $this->getNewArrival(),
            'tile1' => $this->getTile4(),
            'tile2' => $this->getTile1(),
            'tile3' => $this->getTile5(),
            'tile4' => $this->getTile6(),
            'temple_collection' => $this->getTempleCollections(),
            'top_collection' => $this->getTopCollections(),
            'why_choose_us' => $this->getWhyChooseUs(),
            'testimonials' => $this->testimonial->getTestimonials(),
            'about_us_content' => $this->getAboutUsContent()
        ]; 
        return $response;
    }

    // Get Header & Footer Section
    public function getHeaderFooterContent(){
        $response[]= [
            'logo' => $this->getDesktopLogo(),
            'mobile_logo' => $this->getMobileLogo(),
            'mobile_number' => $this->getMobileNumber(),
            'currency' => $this->getCurrency(),
            'email_address' => $this->getEmailAddress(),
            'follow_us' => $this->getFollowUs(),
            'search_terms' => $this->getSearchCollection(),
            'footer_about_us' => $this->getFooterStaticOne(),
            'contact_us' => $this->getContactUs(),
            'overview' => $this->getCmsPageLinks(),
            'payment_method' => $this->getPaymentMethod(),
            'copyright' => $this->getCopyright(),
            'categories' => $this->getMenuCategories()
        ];
        return $response;
    }

    public function getDesktopLogo(){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        try{
            $logo = $this->getConfigValue(ConstantsHelper::HEADER_LOGO);
            if($logo){
                return  $mediaUrl.'logo/'.$logo;
            }
             
        }catch(\Exception $e){
            $this->logger->info('error in logo');
        }
    }

    public function getMobileLogo(){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        try{
            $logo = $this->getConfigValue(ConstantsHelper::MOBILE_LOGO);
            if($logo){
                return $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$logo;
            }
             
        }catch(\Exception $e){
            $this->logger->info('error in logo');
        }
    }

    public function getMobileNumber(){
        $mobileNumber = "";
        try{
            if($this->getConfigValue(ConstantsHelper::MOBILE_NUMBER) != "" || $this->getConfigValue(ConstantsHelper::MOBILE_NUMBER) != null){
                $mobileNumber = $this->getConfigValue(ConstantsHelper::MOBILE_NUMBER);
            }
            return $mobileNumber;
        }catch(\Exception $e){
            $mobile_message = $e->getMessage();
            $this->logger->info('error in mobile number');
        }
    }

    public function getEmailAddress(){
        $emailAddress = "";
        try{
            if($this->getConfigValue(ConstantsHelper::EMIAL_ADDRESS) != null || $this->getConfigValue(ConstantsHelper::EMIAL_ADDRESS) != ""){
                $emailAddress = $this->getConfigValue(ConstantsHelper::EMIAL_ADDRESS);; 
            }
            return $emailAddress;
        }catch(\Exception $e){
            $mobile_message = $e->getMessage();
            $this->logger->info('error in mobile number');
        }
    }

    // Get Meta Title
    public function getMetaTitle(){
        $metaTitle = "";
        try{
            if($this->getConfigValue(ConstantsHelper::META_TITLE) != null || $this->getConfigValue(ConstantsHelper::META_TITLE) != ""){
                $metaTitle = $this->getConfigValue(ConstantsHelper::META_TITLE);; 
            }
            return $metaTitle;
        }catch(\Exception $e){
            $mobile_message = $e->getMessage();
            $this->logger->info('Error occured in meta title');
        }
    }

    // Get Meta Keywords
    public function getMetaKeywords(){
        $metaKeywords = "";
        try{
            if($this->getConfigValue(ConstantsHelper::META_KEYWORDS) != null || $this->getConfigValue(ConstantsHelper::META_KEYWORDS) != ""){
                $metaKeywords = $this->getConfigValue(ConstantsHelper::META_KEYWORDS);; 
            }
            return $metaKeywords;
        }catch(\Exception $e){
            $mobile_message = $e->getMessage();
            $this->logger->info('Error occured in meta keywords');
        }
    }

    // Get Meta Description
    public function getMetaDescription(){
        $metaDescription = "";
        try{
            if($this->getConfigValue(ConstantsHelper::META_DESCRIPTION) != null || $this->getConfigValue(ConstantsHelper::META_DESCRIPTION) != ""){
                $metaDescription = $this->getConfigValue(ConstantsHelper::META_DESCRIPTION);; 
            }
            return $metaDescription;
        }catch(\Exception $e){
            $mobile_message = $e->getMessage();
            $this->logger->info('Error occured in meta description');
        }
    }

    public function getCurrency(){
        return $this->currencyHelper->getCurrentCurrencySymbol();
    }

    public function getParentCategories(){
        // all the menu are used for search by category, but only 'included_in_menu are shown in mega menu.
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $categories = $this->categoryFactory->create()
                    ->addAttributeToFilter('parent_id',$root);
        $parentCategories = [];
        foreach($categories as $cat){
            $categoryInstance = $this->categoryRepository->get($cat->getId());
            $urlKey =  $this->categoryHelper->getCategoryUrlKeyById($cat->getId());
            $isActive = $categoryInstance->getIsActive();
            if($isActive){
                $includeInMenu = $categoryInstance->getIncludeInMenu();
                if($includeInMenu == self::INCLUDEINMENU){
                    $parentCategories [] =  ['category_id'=> $cat->getId(),'name'=>$categoryInstance->getName(),'include_in_menu'=> $includeInMenu,'url'=>$urlKey];
                }
            }
        }
        return $parentCategories;
    }

    public function getBanners()
    {
        $banners = $this->banner->getBanner();
        $newBanners = [];
        foreach($banners as $banner){
            if($banner['desktop']){
                $banner['desktop'] = $this->urlHelper->getMediaUrl().$banner['desktop'];
            }
            if($banner['mobile']){
                $banner['mobile'] = $this->urlHelper->getMediaUrl().$banner['mobile'];
            }
            $newBanners[] = $banner;
        }
        // return $newBanners;   only single banner is allowed.
        return $newBanners;
    }
    
    public function getTile1(){
        $title1 = $this->getConfigValue(ConstantsHelper::TILE1_TITLE);
        $title1 = ($title1) ? $title1 : 'Traditional Diyas';
        $category = $this->getConfigValue(ConstantsHelper::TILE1_CATEGORY);
        $pageSize =  ($this->getConfigValue(ConstantsHelper::TILE1_PAGE_SIZE));
        $tile1['title'] = $title1;
        $tile1['see_more_url_key'] = "";
        if($category){
            $tile1['see_more_url_key'] = $this->categoryHelper->getCategoryUrlKeyById($category);
            
            $tile1['redirect_type'] = 'plp';
            $productDatas = $this->getProductsByCategoryId($category, $pageSize);
        } 
        $tile1['products'] = $productDatas;
        return $tile1;
    }

    public function getProductbySku($sku){
        $productsku = [];
        $sku = trim($sku);
        try{
            $product  = $this->productRepository->get($sku);
            $productId = $product->getId();
            $enabled = $product->getStatus();
            $productType= $product->getTypeID();
            $mediaUrl = $this->urlHelper->getMediaUrl();
            if($enabled == 1 && $productId != ""){
                $productsku['product_id'] = $product->getId();
                $productsku['name'] = $product->getName();
                $productsku['sku'] = $product->getSku();
                $productsku['url'] = $this->categoryHelper->getProductUrlKey($product->getProductUrl());
                $productsku['redirect_type'] = 'pdp' ;
                $productsku['image'] = $this->urlHelper->getPlaceHolderImage();
                if(isset($product['main_image_s3_url']) && $product['main_image_s3_url'] != null && (!empty($product['main_image_s3_url']))){
                    $productsku['image'] = $product['main_image_s3_url'];
                }
                return $productsku;
            }    
        }
        catch(\Exception $e){
            $sku1_message = $e->getMessage();
        }
    }



    public function getTile2(){
        $title2 = $this->getConfigValue(ConstantsHelper::TILE2_TITLE);
        $title2 = ($title2) ? $title2 : 'Idols';
        $category = $this->getConfigValue(ConstantsHelper::TILE2_CATEGORY);
        $image = $this->getConfigValue(ConstantsHelper::TILE2_IMAGE);
        $tile2['title'] =  $title2;
        $tile2['redirect_type'] = 'plp'; 

        if($category){
            $tile2['see_more_url_key']= $this->categoryHelper->getCategoryUrlKeyById($category);
        }
        $mediaUrl = $this->urlHelper->getMediaUrl();
        if($image){
            $tile2['image']= $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$image;
        }
        return $tile2;
    }

    public function getTile3(){
        $title3 = $this->getConfigValue(ConstantsHelper::TILE3_TITLE);
        $title3 = ($title3) ? $title3 : 'Home Decors';
        $category = $this->getConfigValue(ConstantsHelper::TILE3_CATEGORY);
        $image = $this->getConfigValue(ConstantsHelper::TILE3_IMAGE);
        $tile3['title'] =  $title3;
        $tile3['redirect_type'] = 'plp';
        if($category){
            $tile3['see_more_url_key'] = $this->categoryHelper->getCategoryUrlKeyById($category);
        }
        $mediaUrl = $this->urlHelper->getMediaUrl();
        if($image){
            $tile3['image']= $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$image;
        }
        return $tile3;

    }

    public function getTile4(){
        $tile4 = [];
        $url = $this->getConfigValue(ConstantsHelper::TILE4_URL);
        $open_in_new = $this->getConfigValue(ConstantsHelper::TILE4_OPEN_IN_NEW);
        $image = $this->getConfigValue(ConstantsHelper::TILE4_IMAGE);
        $tile4['image'] = $this->urlHelper->getPlaceHolderImage();
        if($image){
            $mediaUrl = $this->urlHelper->getMediaUrl();
            $tile4['image'] =   $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$image;
        }
        $tile4['url'] = "";
        if($url){
            $tile4['url']= $url;
        }
        $tile4['open_in_new'] = "";
        if($open_in_new){
            $tile4['open_in_new'] =  $open_in_new;
        }
        return ($tile4) ?  $tile4: null;

    }

    public function getProductsByCategoryId($category, $pageSize){
        $categories = $this->category->create()->load($category);
        $productDataCollection = $categories->getProductCollection();
        $productDataCollection->addAttributeToSelect('sku');
        $productDataCollection->addAttributeToSelect('entity_id');
        $productDataCollection->addAttributeToSelect('show_in_frontend');
        // Filter displayed product
        $productDataCollection->addAttributeToFilter('show_in_frontend', 1);
        // Filter Product Status
        $productDataCollection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)); // Filter enable product
        // Filter Product Visibility 
        $productDataCollection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE)); 
        if($pageSize && is_numeric($pageSize)){
            $productDataCollection->setPageSize($pageSize);
        }
        $productCollections = $productDataCollection->getData();
        $productCount = count($productDataCollection);
        $productDatas = [];
        if(!empty($productCollections)){
            foreach($productCollections as $productCollection){
                $productDataFactory = $this->productFactory->create()->load($productCollection['entity_id'])->getData();
                    $productDatas[] = $this->getProductbySku($productCollection['sku']); 
            }
        }
        return $productDatas;
    }

    public function getTile5(){
        $title5 = $this->getConfigValue(ConstantsHelper::TILE5_TITLE);
        $category = $this->getConfigValue(ConstantsHelper::TILE5_CATEGORY);
        $pageSize = self::TILE3_PAGESIZE;
        $tile5['see_more_url_key'] = "";
        
        $title5 = ($title5) ? $title5 : 'Shop By Products';
        $tile5['title'] = $title5; 
        $tile5['redirect_type'] = "plp"; 
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $productDatas = [];
        if($category){
            $tile5['see_more_url_key'] = $this->categoryHelper->getCategoryUrlKeyById($category);
            $productDatas = $this->getProductsByCategoryId($category, $pageSize);
        }
        $tile5['products'] = $productDatas;
        return $tile5;
    }

    public function getTile6(){
        $title6 = $this->getConfigValue(ConstantsHelper::TILE6_TITLE);
        $category = $this->getConfigValue(ConstantsHelper::TILE6_CATEGORY);
        $pageSize = self::TILE4_PAGESIZE;
        $title6 = ($title6) ? $title6 : 'Shop By Products';
        $tile6['title'] = $title6; 
        $tile6['redirect_type'] = "plp"; 
        $tile6['see_more_url_key'] = "";
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $productData = [];
        if($category){
            $tile6['see_more_url_key'] = $this->categoryHelper->getCategoryUrlKeyById($category);
            $productData = $this->getProductsByCategoryId($category, $pageSize);
        }
        $tile6['products'] = $productData;
        return $tile6;
    }

    public function getTempleCollections(){
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $categories = $this->categoryFactory->create();
        $categories->addAttributeToSelect('is_active');
        $categories->addAttributeToSelect('include_in_menu');
        $categories->addAttributeToSelect('temple_collection');
        $categories->addAttributeToFilter('is_active', 1);
        $categories->addAttributeToFilter('temple_collection', self::TEMPLE_COLLECTION);
        $templeCollections = [];
        foreach($categories as $cat){
            $categoryInstance = $this->categoryRepository->get($cat->getId());
            $urlKey =  $this->categoryHelper->getCategoryUrlKeyById($cat->getId());
            $isActive = $categoryInstance->getIsActive();
            $includeInMenu = $categoryInstance->getIncludeInMenu();
            $templeCollection = $categoryInstance->getTempleCollection();
            $cat_image = $this->urlHelper->getPlaceHolderImage();
            if($categoryInstance->getImage() != null && $categoryInstance->getImage() != ""){
                $cat_image = $this->urlHelper->getBaseUrl() . $categoryInstance->getImage(); 
            }
            $templeCollections [] =  [
                'category_id'=> $cat->getId(),
                'name'=>$categoryInstance->getName(),
                'include_in_menu'=> $includeInMenu,
                'url'=>$urlKey,
                'image' => $cat_image
            ];
        }
        return $templeCollections;
    }

    public function getTopCollections(){
        $root = $this->storeManager->getStore()->getRootCategoryId();
        $categories = $this->categoryFactory->create();
        $categories->addAttributeToSelect('is_active');
        $categories->addAttributeToSelect('include_in_menu');
        $categories->addAttributeToSelect('top_collection');
        $categories->addAttributeToFilter('is_active', 1);
        $categories->addAttributeToFilter('top_collection', self::TOP_COLLECTION);
        $templeCollections = [];
        foreach($categories as $cat){
            $categoryInstance = $this->categoryRepository->get($cat->getId());
            $urlKey =  $this->categoryHelper->getCategoryUrlKeyById($cat->getId());
            $isActive = $categoryInstance->getIsActive();
            $includeInMenu = $categoryInstance->getIncludeInMenu();
            $templeCollection = $categoryInstance->getTopCollection();
            if($categoryInstance->getImage() != null && $categoryInstance->getImage() != ""){
                $cat_image =  $this->urlHelper->getBaseUrl() . $categoryInstance->getImage();
            }
            else{
                $cat_image =  $this->urlHelper->getPlaceHolderImage();
            }
            $templeCollections [] =  [
                'category_id' => $cat->getId(),
                'name' => $categoryInstance->getName(),
                'include_in_menu' => $includeInMenu,
                'url' => $urlKey,
                'image' => $cat_image
            ];
        }
        return $templeCollections;
    }

    public function getTile7(){
        $title7 = $this->getConfigValue(ConstantsHelper::TILE7_TITLE);
        $title7 = ($title7) ? $title7 : 'Home Decors';
        $category = $this->getConfigValue(ConstantsHelper::TILE7_CATEGORY);
        $image = $this->getConfigValue(ConstantsHelper::TILE7_IMAGE);
        $tile7['title'] =  $title7;
        $tile7['redirect_type'] = 'plp';
        if($category){
            $tile7['see_more_url_key'] = $this->categoryHelper->getCategoryUrlKeyById($category);
        }
        $mediaUrl = $this->urlHelper->getMediaUrl();
        if($image){
            $tile7['image']= $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$image;
        }
        return $tile7;
    }

    public function getTile8(){
        $title8 = $this->getConfigValue(ConstantsHelper::TILE8_TITLE);
        $title8 = ($title8) ? $title8 : 'Vintage Items';
        $category = $this->getConfigValue(ConstantsHelper::TILE8_CATEGORY);
        $image = $this->getConfigValue(ConstantsHelper::TILE8_IMAGE);
        $tile8['title'] =  $title8;
        $tile8['redirect_type'] = 'plp';
        if($category){
            $tile8['see_more_url_key'] = $this->categoryHelper->getCategoryUrlKeyById($category);
        }
        $mediaUrl = $this->urlHelper->getMediaUrl();
        if($image){
            $tile8['image']= $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$image;
        }
        return $tile8;
    }

    public function getOfferProduct(){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $collection = $this->productCollection->create();
                $collection->addAttributeToSelect('*');
                $collection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
                $collection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE));
                $collection->addAttributeToSelect('special_price');
                $collection->addAttributeToFilter('special_price', ['notnull' => true]);
               $count = (count($collection));

        if($count > 0){
            $now = $this->dateTime->gmtDate();
            $now = date('Y-m-d 00:00:00',strtotime($now));
             $offer_title ='Offer Product';
                $productArray = [];
                $products = $collection;
                $productCount = count($products);
                if($productCount > 0){
                    foreach($products as $product){
                        $validate=$this->cmsPlpPdp->getSpecialPriceByProductId($product->getId());
                        if($validate['tag'] == 'On Offer'){
                        $productData['id'] = $product->getId();
                        $productData['name'] = $product->getName();
                        $productData['type_id'] = $product->getTypeId();
                        $productData['sku'] = $product->getSku();
                        $productData['short_description'] = $product->getShortDescription();
                        $productType = $product->getTypeId();
                        $productDatasFactory = $this->productFactory->create()->load($product->getId());
                        // Get Media Gallery Images
                        $productData['media_gallery'] = $this->cmsPlpPdp->GetMediaGalleryImage($productDatasFactory);
                        $productData['min_qty'] = $this->cmsPlpPdp->getMinSaleQtyById($product->getId());
                        $newFrom = $product->getNewsFromDate();
                        $newTo = $product->getNewsToDate(); 
                        $productData['price'] = "";
                        $productData['display_price'] = "";
                        $productData['special_price'] = "";
                        $productData['display_special_price'] = "";
                        $newArrivalOnOfferTag = [];
                        $newArrivalOnOfferTag['on_offer'] = "";
                        $tags = [];
                        $newArrivalOnOfferTag['new_arrival'] = '';
                        if($newFrom <= $now && $newTo >= $now ){
                            $newArrivalOnOfferTag['new_arrival'] = 'New Arrival';
                        }
                        $productData['url_key'] = $this->categoryHelper->getProductUrlKey($product->getProductUrl());
                        $productDatas = $this->productFactory->create()->load($product->getId());
                        // Get Product Weight
                        $productData['size_in_kg'] = $this->productHelper->getSizeAttributeValue($productDatas);
                        // get Product Color
                        $productData['color'] = $this->productHelper->getColorAttributeValue($productDatas);
                        $productData['starting_from_price'] = "";
                        $productData['starting_from_display_price'] = "";
                        $productData['starting_to_price'] = "";
                        $productData['starting_to_display_price'] = "";
                        $productData['quantity'] = "";
                        if($productType == "simple"){
                            // get salable quantity
                            $qty = $this->productHelper->getSalableQuantity($productDatas->getData()['sku']);
                            // get stock status
                            if($qty > 0){
                                $productData['stock_status'] = "In stock";
                            }
                            else{
                                $productData['stock_status'] = "Out of Stock";
                            }
                            // get product quantity
                            $productData['quantity'] = $qty;
                            $productData['material'] = $this->productHelper->getMaterialAttributeValue($productDatasFactory);
                            if($productDatas['type_id'] == "simple"){
                                $length= $productDatas['length'];
                                $productData['length'] = number_format((float)$length, 1, '.', '');
                                $width = $productDatas['width'];
                                $productData['width'] = number_format((float)$width, 1, '.', '');
                                $height = $productDatas['height'];
                                $productData['height'] = number_format((float)$height, 1, '.', '');
                                }
                            $productData['price'] = $this->productHelper->getFormattedPrice($product->getPrice());
                            $productData['display_price'] = $this->productHelper->INDMoneyFormat($product->getPrice());
                            $specialPriceByProductId = $this->cmsPlpPdp->getSpecialPriceByProductId($product->getId());
                            if($specialPriceByProductId['special_price'] != ""){
                                $productData['special_price'] = $this->productHelper->getFormattedPrice($specialPriceByProductId['special_price']);
                                $productData['display_special_price'] = $this->productHelper->INDMoneyFormat($specialPriceByProductId['special_price']);
                                $newArrivalOnOfferTag['on_offer'] = $specialPriceByProductId['tag'];
                            }
                        }
                        elseif($productType == "grouped"){
                            $groupedProducts = $this->cmsPlpPdp->getGroupedProductPrice($productDatas);
                            $productData['starting_from_price']  = $groupedProducts['starting_from_price'];
                            $productData['starting_from_display_price'] = $groupedProducts['starting_from_display_price'];
                            $productData['starting_to_price'] = $groupedProducts['starting_to_price'];
                            $productData['starting_to_display_price'] = $groupedProducts['starting_to_display_price'];
                            $newArrivalOnOfferTag['new_arrival'] = $groupedProducts['tags']['new_arrival'];
                            $newArrivalOnOfferTag['on_offer'] = $groupedProducts['tags']['on_offer'];
                            $productData['children_count'] = $groupedProducts['children_count'];
                            // Get Stock Status
                            $_children = $productDatas->getTypeInstance(true)->getAssociatedProducts($productDatas);
                            $count = 0;
                            foreach($_children as $child){
                                $childItemStockStatus = $child->getId();
                                $isInstockStatus = $this->cmsPlpPdp->getStockStatus($child->getId());
                                $groupedChildProduct = $this->productHelper->getSalableQuantity($child->getSku());
                                if ($groupedChildProduct > 0 && $child->getId() != $productDatas->getId() && $child->getStatus() == CmsPlpPdp::ENABLED && $isInstockStatus == CmsPlpPdp::ISINSTOCK) {
                                    $count++;
                                }
                            }
                            if($productDatas['quantity_and_stock_status']['is_in_stock'] == true && $count != 0){
                                $productData['stock_status'] = "In stock";
                            }
                            else{
                                $productData['stock_status'] = "Out of stock";
                            }
                        }
                        $tags = $newArrivalOnOfferTag;
                        $productData['tag'] = $tags;
                        // Get Grouped Product
                        $groupedProductDatas = [];
                        if($productType == "grouped"){
                            $groupedProductDatas = $this->cmsPlpPdp->getGroupedProduct($product->getId());
                            $productData ['grouped_product'] =  $groupedProductDatas;
                        }
                        $productData ['grouped_product'] =  $groupedProductDatas;
                        $productArray[] = $productData;
                    }
                }
                }
                $offerProduct['title'] = $offer_title;
                $offerProduct['products'] = $productArray;
                return $offerProduct;
            
        }
        else{
            return [];
        }
    }
    public function getTrending(){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        if($this->getConfigValue(ConstantsHelper::TRENDING_ENABLED)){
            $now = $this->dateTime->gmtDate();
            $now = date('Y-m-d 00:00:00',strtotime($now));
            $categoryId = $this->getConfigValue(ConstantsHelper::TRENDING_CATEGORY);   
            $trending_title = $this->getConfigValue(ConstantsHelper::TRENDING_TITLE);        
            $limit = $this->getConfigValue(ConstantsHelper::TRENDING_LIMIT);  
            $trending_title =  ($trending_title) ? $trending_title : 'Trending Now';
            if($categoryId){
                $collection = $this->productCollection->create();
                $collection->addAttributeToSelect('*');
                $collection->getSelect()->orderRand() ;
                // Filter Product Status
                $collection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
                // Filter Product Visibility
                $collection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE));
                $collection->addAttributeToFilter('trending_product',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
                if($limit && is_numeric($limit)){
                    $collection->setPageSize($limit);
                }
                $productArray = [];
                $products = $collection;
                $productCount = count($products);
                if($productCount > 0){
                    foreach($products as $product){
                        $productData['id'] = $product->getId();
                        $productData['name'] = $product->getName();
                        $productData['type_id'] = $product->getTypeId();
                        $productData['sku'] = $product->getSku();
                        $productData['short_description'] = $product->getShortDescription();
                        $productType = $product->getTypeId();
                        $productDatasFactory = $this->productFactory->create()->load($product->getId());
                        // Get Media Gallery Images
                        $productData['media_gallery'] = $this->cmsPlpPdp->GetMediaGalleryImage($productDatasFactory);
                        $productData['min_qty'] = $this->cmsPlpPdp->getMinSaleQtyById($product->getId());
                        $newFrom = $product->getNewsFromDate();
                        $newTo = $product->getNewsToDate(); 
                        $productData['price'] = "";
                        $productData['display_price'] = "";
                        $productData['special_price'] = "";
                        $productData['display_special_price'] = "";
                        $newArrivalOnOfferTag = [];
                        $newArrivalOnOfferTag['on_offer'] = "";
                        $tags = [];
                        $newArrivalOnOfferTag['new_arrival'] = '';
                        if($newFrom <= $now && $newTo >= $now ){
                            $newArrivalOnOfferTag['new_arrival'] = 'New Arrival';
                        }
                        $productData['url_key'] = $this->categoryHelper->getProductUrlKey($product->getProductUrl());
                        $productDatas = $this->productFactory->create()->load($product->getId());
                        // Get Product Weight
                        $productData['size_in_kg'] = $this->productHelper->getSizeAttributeValue($productDatas);
                        // get Product Color
                        $productData['color'] = $this->productHelper->getColorAttributeValue($productDatas);
                        $productData['starting_from_price'] = "";
                        $productData['starting_from_display_price'] = "";
                        $productData['starting_to_price'] = "";
                        $productData['starting_to_display_price'] = "";
                        $productData['quantity'] = "";
                        if($productType == "simple"){
                            // get salable quantity
                            $qty = $this->productHelper->getSalableQuantity($productDatas->getData()['sku']);
                            // get stock status
                            if($qty > 0){
                                $productData['stock_status'] = "In stock";
                            }
                            else{
                                $productData['stock_status'] = "Out of Stock";
                            }
                            // get product quantity
                            $productData['quantity'] = $qty;
                            $productData['material'] = $this->productHelper->getMaterialAttributeValue($productDatasFactory);
                            if($productDatas['type_id'] == "simple"){
                                $length= $productDatas['length'];
                                $productData['length'] = number_format((float)$length, 1, '.', '');
                                $width = $productDatas['width'];
                                $productData['width'] = number_format((float)$width, 1, '.', '');
                                $height = $productDatas['height'];
                                $productData['height'] = number_format((float)$height, 1, '.', '');
                                }
                            $productData['price'] = $this->productHelper->getFormattedPrice($product->getPrice());
                            $productData['display_price'] = $this->productHelper->INDMoneyFormat($product->getPrice());
                            $specialPriceByProductId = $this->cmsPlpPdp->getSpecialPriceByProductId($product->getId());
                            if($specialPriceByProductId['special_price'] != ""){
                                $productData['special_price'] = $this->productHelper->getFormattedPrice($specialPriceByProductId['special_price']);
                                $productData['display_special_price'] = $this->productHelper->INDMoneyFormat($specialPriceByProductId['special_price']);
                                $newArrivalOnOfferTag['on_offer'] = $specialPriceByProductId['tag'];
                            }
                        }
                        elseif($productType == "grouped"){
                            $groupedProducts = $this->cmsPlpPdp->getGroupedProductPrice($productDatas);
                            $productData['starting_from_price']  = $groupedProducts['starting_from_price'];
                            $productData['starting_from_display_price'] = $groupedProducts['starting_from_display_price'];
                            $productData['starting_to_price'] = $groupedProducts['starting_to_price'];
                            $productData['starting_to_display_price'] = $groupedProducts['starting_to_display_price'];
                            $newArrivalOnOfferTag['new_arrival'] = $groupedProducts['tags']['new_arrival'];
                            $newArrivalOnOfferTag['on_offer'] = $groupedProducts['tags']['on_offer'];
                            $productData['children_count'] = $groupedProducts['children_count'];
                            // Get Stock Status
                            $_children = $productDatas->getTypeInstance(true)->getAssociatedProducts($productDatas);
                            $count = 0;
                            foreach($_children as $child){
                                $childItemStockStatus = $child->getId();
                                $isInstockStatus = $this->cmsPlpPdp->getStockStatus($child->getId());
                                $groupedChildProduct = $this->productHelper->getSalableQuantity($child->getSku());
                                if ($groupedChildProduct > 0 && $child->getId() != $productDatas->getId() && $child->getStatus() == CmsPlpPdp::ENABLED && $isInstockStatus == CmsPlpPdp::ISINSTOCK) {
                                    $count++;
                                }
                            }
                            if($productDatas['quantity_and_stock_status']['is_in_stock'] == true && $count != 0){
                                $productData['stock_status'] = "In stock";
                            }
                            else{
                                $productData['stock_status'] = "Out of stock";
                            }
                        }
                        $tags = $newArrivalOnOfferTag;
                        $productData['tag'] = $tags;
                        // Get Grouped Product
                        $groupedProductDatas = [];
                        if($productType == "grouped"){
                            $groupedProductDatas = $this->cmsPlpPdp->getGroupedProduct($product->getId());
                            $productData ['grouped_product'] =  $groupedProductDatas;
                        }
                        $productData ['grouped_product'] =  $groupedProductDatas;
                        $productArray[] = $productData;
                    }
                }
                $trending['title'] = $trending_title;
                $trending['products'] = $productArray;
                return $trending;
            } 
        }
        else{
            return [];
        }
    }

    public function getNewArrival(){
        $newArrival = [];
        $title = $this->getConfigValue(ConstantsHelper::NEWARRIVAL_TITLE); 
        $newarrival_title =  ($title) ? $title : 'Trending Now';
        if($this->getConfigValue(ConstantsHelper::NEWARRIVAL_ENABLED)){
            $now = $this->dateTime->gmtDate();
            $now = date('Y-m-d 00:00:00',strtotime($now));
            $limit = $this->getConfigValue(ConstantsHelper::NEWARRIVAL_LIMIT);  
            $collection = $this->productCollection->create();
            $collection->addAttributeToSelect('status');
            $collection->addAttributeToSelect('visibility');
            $collection->addAttributeToSelect('news_from_date');
            $collection->addAttributeToSelect('news_to_date');
            $collection->addAttributeToSelect('name');
            $collection->addAttributeToSelect('main_image_s3_url');
            $collection->getSelect()->orderRand() ;
            // Filter Product Status
            $collection->addAttributeToFilter('status',array('eq' =>\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
            // Filter Product Visibility
            $collection->addAttributeToFilter('visibility',array('neq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE));
            $collection->addAttributeToFilter('news_from_date', array('lteq'=>$now));
            $collection->addAttributeToFilter('news_to_date', array('gteq'=>$now));
            if($limit && is_numeric($limit)){
                $collection->setPageSize($limit);
            }
            $productArray = [];
            $productData = [];
            $products = $collection;
            $productCount = count($products);
            if($productCount > 0){
                foreach($products as $product){
                    $newFrom = $product->getNewsFromDate();
                    $newTo = $product->getNewsToDate(); 
                    $productData['name'] = $product->getName();
                    $productData['sku'] = $product->getSku();  
                    $productData['url'] = $this->categoryHelper->getProductUrlKey($product->getProductUrl());
                    $productData['image'] = $this->urlHelper->getPlaceHolderImage(); 
                    if(isset($product['main_image_s3_url']) && (!empty($product['main_image_s3_url']) && $product['main_image_s3_url'] != null)){
                        $productData['image'] = $product['main_image_s3_url'];
                    }
                    $productArray[] = $productData;
                }
            }
            $newArrival['title'] = $newarrival_title;
            $newArrival['products'] = $productArray;
        }
        return $newArrival;
    }

    public function getOffersForYou(){
        $now = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $offerData = [];
        $offers = $this->offerCollection->create()
                ->addFieldToSelect(['title','image','limitted_tag','percentage','category'])
                ->addFieldToFilter('is_active',1)
                ->addFieldToFilter('valid_from', array('lteq'=>$now)) 
                ->addFieldToFilter('valid_to', array('gteq'=>$now))
                ->getData();
        $mediaUrl = $this->urlHelper->getMediaUrl();
        foreach($offers as $offer){
            if($offer['image']){
                $offer['image'] = $mediaUrl.$offer['image'];
            }
            else{
                $offer['image'] = $this->urlHelper->getPlaceHolderImage();
            }
            $catId = (int)$offer['category'];
            $categoryUrl = $this->categoryHelper->getCategoryUrlKeyById($catId);
            $offer['categoryUrl'] = $categoryUrl;
            $offer['redirect_type'] = 'plp';
            $offerData[] = $offer;
        }

        return $offerData;
    }

    public function getTemplateTiles(){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $placeHolderImageUrl = $this->urlHelper->getPlaceHolderImage();
        $template_tiles = [];
        try{
            $template_tiles1_title = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE1_TITLE);
            $template_tiles1_content = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE1_CONTENT);
            $template_tiles1_btn_txt = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE1_BUTTON_TEXT);
            $template_tiles1_btn_link = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE1_BUTTON_LINK);
            $template_tiles1_image = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE1_IMAGE);
            $template_tiles2_btn_txt = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE2_BUTTON_TEXT);
            $template_tiles2_btn_link = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE2_BUTTON_LINK);
            $template_tiles2_image = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE2_IMAGE);
            $template_tiles3_btn_txt = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE3_BUTTON_TEXT);
            $template_tiles3_btn_link = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE3_BUTTON_LINK);
            $template_tiles3_image = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE3_IMAGE);
            $template_tiles4_btn_txt = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE4_BUTTON_TEXT);
            $template_tiles4_btn_link = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE4_BUTTON_LINK);
            $template_tiles4_image = $this->getConfigValue(ConstantsHelper::TEMPLATE_TILE4_IMAGE);
        
            if($template_tiles1_image == "") {
                $template_tiles1_image = $placeHolderImageUrl;
            }
            else{
                $template_tiles1_image =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$template_tiles1_image;
            }
            $template_tiles['tile1'] = [
                'image' => $template_tiles1_image,
                'title' => $template_tiles1_title,
                'content' => $template_tiles1_content,
                'button_text' => $template_tiles1_btn_txt,
                'button_link' => $template_tiles1_btn_link 
            ];
            $template_tiles['other_tiles'] = [];
            if($template_tiles2_image == "") {
                $template_tiles2_image = $placeHolderImageUrl;
            }
            else{
                $template_tiles2_image =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$template_tiles2_image;
            }
            array_push($template_tiles['other_tiles'], [
                'image' => $template_tiles2_image,
                'button_text' => $template_tiles2_btn_txt,
                'button_link' => $template_tiles2_btn_link 
            ]);
            if($template_tiles3_image == "") {
                $template_tiles3_image = $placeHolderImageUrl;
            }
            else{
                $template_tiles3_image =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$template_tiles3_image;
            }
            array_push($template_tiles['other_tiles'], ['image' => $template_tiles3_image, 
                'button_text' => $template_tiles3_btn_txt,
                'button_link' => $template_tiles3_btn_link 
            ]);
            if($template_tiles4_image == "") {
                $template_tiles4_image = $placeHolderImageUrl;
            }
            else{
                $template_tiles4_image =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$template_tiles4_image;
            }
            array_push($template_tiles['other_tiles'], [
                'image' => $template_tiles4_image,
                'button_text' => $template_tiles4_btn_txt,
                'button_link' => $template_tiles4_btn_link 
            ]);
            return $template_tiles;
        }catch(\Exception $e){
            $template_tiles_message = $e->getMessage();
            $this->logger->info('error in template tiles ');
        }
    }

    public function getWhyChooseUs(){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $why_choose_us = [];
        try{
            $why_choose_caption1 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_CAPTION1);
            $why_choose_image1 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE1);
            $why_choose_image_1 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE_1);
            if($why_choose_image1 && $why_choose_image_1){
                $why_choose_image1 =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image1;
                array_push($why_choose_us,[ 'caption'=> $why_choose_caption1, 'hover_image'=> $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image_1, 'image'=> $why_choose_image1]);
            }

            $why_choose_caption2 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_CAPTION2);
            $why_choose_image2 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE2);
            $why_choose_image_2 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE_2);
            if($why_choose_image2 && $why_choose_image_2){
                $why_choose_image2 =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image2;
                array_push($why_choose_us,[ 'caption'=> $why_choose_caption2, 'hover_image'=> $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image_2, 'image'=> $why_choose_image2]);
            }

            $why_choose_caption3 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_CAPTION3);
            $why_choose_image3 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE3);
            $why_choose_image_3 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE_3);
            if($why_choose_image3 && $why_choose_image_3){
                $why_choose_image3 =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image3;
                array_push($why_choose_us,[ 'caption'=> $why_choose_caption3, 'hover_image'=> $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image_3, 'image'=> $why_choose_image3 ]);
            }

            $why_choose_caption4 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_CAPTION4);
            $why_choose_image4 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE4);
            $why_choose_image_4 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE_4);
            if($why_choose_image4 && $why_choose_image_4){
                $why_choose_image4 =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image4;
                array_push($why_choose_us,[ 'caption'=> $why_choose_caption4, 'hover_image'=> $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image_4, 'image'=> $why_choose_image4 ]);
            }


            $why_choose_caption5 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_CAPTION5);
            $why_choose_image5 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE5);
            $why_choose_image_5 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE_5);
            if($why_choose_image5 && $why_choose_image_5){
                $why_choose_image5 =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image5;
                array_push($why_choose_us,[ 'caption'=> $why_choose_caption5, 'hover_image'=> $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image_5, 'image'=> $why_choose_image5 ]);
            }


            $why_choose_caption6 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_CAPTION6);
            $why_choose_image6 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE6);
            $why_choose_image_6 = $this->getConfigValue(ConstantsHelper::WHY_CHOOSE_US_IMAGE_6);
            if($why_choose_image6 && $why_choose_image_6){
                $why_choose_image6 =  $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image6;
                array_push($why_choose_us,[ 'caption'=> $why_choose_caption6, 'hover_image'=> $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$why_choose_image_6, 'image'=> $why_choose_image6 ]);
            } 

            return $why_choose_us;
        }catch(\Exception $e){
            $why_choose_us_message = $e->getMessage();
            $this->logger->info('error in why choose us');
        }
    }

    public function getAboutUsContent(){
        try{ 
            $aboutUs = [];
            $strip_tags = "div";
            $status = $this->blockRepository->getById('about')->getIsActive();
            if($status == CmsPlpPdp::ENABLED){
                $aboutUs['title'] =  $this->blockRepository->getById('about')->getTitle();
                $content3 = $this->blockRepository->getById('about')->getContent();
                $content3 = $this->getHtmlTags($content3);
                $content3 = preg_replace("#<\s*\/?(".$strip_tags.")\s*[^>]*?>#im", '', $content3);
                $aboutUs['about_us'] =  $content3;
                $aboutUs['read_more_url'] =  self::ABOUT_US_URL_KEY;
                $this->logger->info('about us content done');
            }
            return $aboutUs;
        }catch(\Exception $e){
            $footer_message = $e->getMessage();
            $this->logger->info($e->getMessage());
        }
    }

    public function getFooterStaticOne(){
        try{
            $footer_static1 = [];
            $strip_tags = "div";
            $status =  $this->blockRepository->getById('footer_static1')->getIsActive();
            if($status == CmsPlpPdp::ENABLED){
                $footer_static1['title'] =  $this->blockRepository->getById('footer_static1')->getTitle();
                $content1 = $this->blockRepository->getById('footer_static1')->getContent();
                $content1 = $this->getHtmlTags($content1);
                $content1 = preg_replace("#<\s*\/?(".$strip_tags.")\s*[^>]*?>#im", '', $content1);
                $footer_static1['content'] =  str_replace('\"','"', $content1);
                $this->logger->info('footer static 1 done');
            }
            return $footer_static1;
        }catch(\Exception $e){
            $footer_message = $e->getMessage();
        }
    }

    public function getCmsPageLinks(){
        $cms_page = [];
        $cms_links_array = [];
        $overview_link = [];
        $cms_links_array['title'] = "Overview";
        try{
        $cms_links = $this->getConfigValue(ConstantsHelper::CMS_LINKS);
        if($cms_links){
            $cms_pages_arrays = explode(';',$cms_links);
            if($cms_pages_arrays){
                $no= 1;
                foreach($cms_pages_arrays as $page){
                    if(trim($page) !=''){ 
                        $page_text = explode('::',$page);
                        $page_link = explode('::',$page);
                        $urlKey = ['request_path' => trim($page_link[1])];
                        $rewrite = $this->urlFinderInterface->findOneByData($urlKey);
                        if($rewrite){
                            $entityId =  $rewrite->getEntityId();
                            $staticPage = $this->pageRepository->getById($entityId)->getData();
                            // Checking show only enabled cms page link
                            if($staticPage['is_active'] == CmsPlpPdp::ENABLED){
                                $cms_page  = ['title' => ($page_text[0]) ? (trim($page_text[0])) :'' , 'link' => ($page_link[1]) ? trim($page_link[1]) : ''];
                                $overview_link[] = $cms_page;
                            }
                        }
                    }
                    $no++;
                }
            }
        } 
        $cms_links_array['data'] = $overview_link;
        return $cms_links_array;
        }catch(\Exception $e){
            echo $cms_link_message = $e->getMessage();
            $this->logger->info('error in why cms page links');
        }
    }

    public function getShopNowPageLinks(){
        try{
            $shopNowLink= []; 
            $shopNow =[];
            $shopNowLink['title'] = "Shop Now";
            $mediaUrl = $this->urlHelper->getMediaUrl();
            $title1 = $this->getConfigValue(ConstantsHelper::SHOPNOW_TITILE1); 
            $url1 = $this->getConfigValue(ConstantsHelper::SHOPNOW_URL1); 
            if($title1 != NULL && $title1 != "" && $url1 != NULL && $url1 != ""){
                array_push($shopNow, ['name'=>$title1, 'link'=>$url1]);
            }
            $title2 = $this->getConfigValue(ConstantsHelper::SHOPNOW_TITILE2); 
            $url2 = $this->getConfigValue(ConstantsHelper::SHOPNOW_URL2); 
            if($title2 != NULL && $title2 != "" && $url2 != NULL && $url2 != ""){
                array_push($shopNow, ['name'=>$title2, 'link'=>$url2]);
            }
            $title3 = $this->getConfigValue(ConstantsHelper::SHOPNOW_TITILE3); 
            $url3 = $this->getConfigValue(ConstantsHelper::SHOPNOW_URL3); 
            if($title3 != NULL && $title3 != "" && $url3 != NULL && $url3 != ""){
                array_push($shopNow, ['name'=>$title3, 'link'=>$url3]);
            }
            $title4 = $this->getConfigValue(ConstantsHelper::SHOPNOW_TITILE4); 
            $url4 = $this->getConfigValue(ConstantsHelper::SHOPNOW_URL4); 
            if($title4 != NULL && $title4 != "" && $url4 != NULL && $url4 != ""){   
                array_push($shopNow, ['name'=>$title4, 'link'=>$url4]);
            }
            $title5 = $this->getConfigValue(ConstantsHelper::SHOPNOW_TITILE5); 
            $url5 = $this->getConfigValue(ConstantsHelper::SHOPNOW_URL5); 
            if($title5 != NULL && $title5 != "" && $url5 != NULL && $url5 != ""){
                array_push($shopNow, ['name'=>$title5, 'link'=>$url5]);
            }
            $shopNowLink['data'] = $shopNow;
            return $shopNowLink;
        }catch(\Exception $e){
            echo $shopnow_link_message = $e->getMessage();
            $this->logger->info('error in why shopnow page links');
        }
    }

    public function getContactUs(){
        try{ 
            $contact_us = [];
            $strip_tags = "div";
            $status = $this->blockRepository->getById('address')->getIsActive();
            if($status == CmsPlpPdp::ENABLED){
                $contact_us['title'] =  $this->blockRepository->getById('address')->getTitle();
                $content3 = $this->blockRepository->getById('address')->getContent();
                $content3 = $this->getHtmlTags($content3);
                $content3 = preg_replace("#<\s*\/?(".$strip_tags.")\s*[^>]*?>#im", '', $content3);
                $contact_us['address'] =  $content3;
                $metaTitle = $this->getConfigValue(self::META_TITLE);
                $metaKeywords = $this->getConfigValue(self::META_KEYWORDS);
                $metaDescription = $this->getConfigValue(self::META_DESCRIPTION);
                $contact_us['meta_title'] = isset($metaTitle) ? $metaTitle: "";
                $contact_us['meta_keywords']=isset($metaKeywords) ? $metaKeywords: "";
                $contact_us['meta_description']=isset($metaDescription) ? $metaDescription: "";
                $this->logger->info('contact us done');
            }
            return $contact_us;
        }catch(\Exception $e){
            $footer_message = $e->getMessage();
        }
    }

    public function getLandLine(){

        try{
            return $this->getConfigValue(ConstantsHelper::LAND_LINE);
        }catch(\Exception $e){
            $land_line_message = $e->getMessage();
            $this->logger->info('error in land line');
        }
    }

    public function getSearchCollection()
    {
        $searchResult = [];
        $overallSearchResult = [];
        $searchTitle = "Popular Search";
        $productName = "";
        $searchResult['product_name'] = "";
        $searchItems = $this->query->getSuggestCollection()
                                ->addFieldtoSelect('query_text')
                                ->setPageSize(self::PAGE_SIZE)
                                ->setCurPage(self::PAGE_COUNT);
        $searchItems->getData();
        foreach($searchItems as $searchItems){
            $sku = trim($searchItems['query_text']);
            $searchResult['query_text'] = $searchItems['query_text'];
            $searchResult['product_url'] = "";
            $product  = $this->productCollection->create();
            $product->addAttributeToSelect('*');
            $product->addFieldToFilter('sku', $sku);
            $productId = "";
            if(!empty($product->getData())){
                foreach($product->getData() as $productData){
                    $productData  = $this->productRepository->get($sku);
                    $productId = $productData['entity_id'];
                    $productName = $productData->getProductName();
                    $searchResult['product_url'] = $this->productHelper->getProductRewriteUrl($productId);
                    $searchResult['product_name'] = $productName;
                }
            }
            $overallSearchResult[] = $searchResult;
        }
        $overallSearchResults['title'] = $searchTitle;
        $overallSearchResults['search_data_item'] = $overallSearchResult;
        return $overallSearchResults;
    }

    public function getPaymentMethod(){
        try{
            $mediaUrl = $this->urlHelper->getMediaUrl();
            $payment_method = $this->getConfigValue(ConstantsHelper::PAYMENT_METHOD_IMAGE);
            $payment_title = $this->getConfigValue(ConstantsHelper::PAYMENT_METHOD_TITLE);
            $paymentMethod = [];
            if($payment_method){
                $paymentMethod['title'] = $payment_title;
                $paymentMethod['image'] = $mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$payment_method;
            }
            return $paymentMethod;
        }catch(\Exception $e){
            $payment_method_message = $e->getMessage();
            $this->logger->info('error in payment method');
        }
    }

    public function getFollowUs(){
        try{
            $follow_us= [];
            $mediaUrl = $this->urlHelper->getMediaUrl();
            $facebook_title = $this->getConfigValue(ConstantsHelper::FACEBOOK_TITLE); 
            $facebook_url = $this->getConfigValue(ConstantsHelper::FACEBOOK_URL); 
            $facebook_image = $this->getConfigValue(ConstantsHelper::FACEBOOK_HEADER_IMAGE); 
            $facebook_footer_image = $this->getConfigValue(ConstantsHelper::FACEBOOK_FOOTER_IMAGE); 
            if($facebook_image){
                array_push($follow_us, ['title'=>$facebook_title, 'link'=>$facebook_url,'header_image'=>$mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$facebook_image,'footer_image'=>$mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$facebook_footer_image]);
            }
            $twitter_title = $this->getConfigValue(ConstantsHelper::TWITTER_TITLE); 
            $twitter_url = $this->getConfigValue(ConstantsHelper::TWITTER_URL); 
            $twitter_image = $this->getConfigValue(ConstantsHelper::TWITTER_HEADER_IMAGE); 
            $twitter_footer_image = $this->getConfigValue(ConstantsHelper::TWITTER_FOOTER_IMAGE); 
            if($twitter_image){
                array_push($follow_us, ['title'=>$twitter_title, 'link'=>$twitter_url,'header_image'=>$mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$twitter_image, 'footer_image'=>$mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$twitter_footer_image]);
            }
            $instagram_title = $this->getConfigValue(ConstantsHelper::INSTAGRAM_TITLE); 
            $instagram_url = $this->getConfigValue(ConstantsHelper::INSTAGRAM_URL); 
            $instagram_image = $this->getConfigValue(ConstantsHelper::INSTAGRAM_HEADER_IMAGE); 
            $instagram_footer_image = $this->getConfigValue(ConstantsHelper::INSTAGRAM_FOOTER_IMAGE); 
            if($instagram_image){
                array_push($follow_us, ['title'=>$instagram_title, 'link'=>$instagram_url,'header_image'=>$mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$instagram_image, 'footer_image'=>$mediaUrl.ConstantsHelper::HOME_PAGE_FOLDER.$instagram_footer_image]);
            }
            return $follow_us;
        }catch(\Exception $e){
            $follow_us_message = $e->getMessage();
            $this->logger->info('error in Follow Us');
        }
    }

    public function getCopyright(){
        try{
            return $this->getConfigValue(ConstantsHelper::FOOTER_COPY_RIGHT);
        }catch(\Exception $e){
            $logo_message = $e->getMessage();
            $this->logger->info('error in copyright');
        }
    }
    // Get menu Categories
    public function getMenuCategories(){
        try{
            return $this->getAllCategories();
        }
        catch(\Exception $e){
            $all_category_message = $e->getMessage();
            $this->logger->info('error in getting include in menu categories');
        }
    }
    // Get all Categories
    public function getAllCategories(){
        try{
            return $this->cmsPlpPdp->getAllCategories();
        }
        catch(\Exception $e){
            $all_category_message = $e->getMessage();
            $this->logger->info('error in getting all categories');
        }
    }
    
    public function getHtmlTags($html)
    {
        preg_match_all(
            '/\<\w[^<>]*?\>([^<>]+?\<\/\w+?\>)?|\<\/\w+?\>/i',
            $html,
            $matches
        );

        $html = $matches[0];
        unset($html[0]);
        $content = '';
        foreach($html as $ht){
            $content  .= $ht;                
        }
        return $content;
    }

    public function getIncludeInMenuCategories(){
        $categoryId = $root = $this->storeManager->getStore()->getRootCategoryId();
        $allCategroy = [];
        $mainCategory = [];
        $subCategory = [];
        $categories = $this->category->create()->load($categoryId);
        $mainCategory['id'] = $categories->getId();
        $mainCategory['name'] = $categories->getName();
        if($categories->getImage() != Null){
            $mainCategory["image"] = $this->urlHelper->getBaseUrl() . $categories->getImage();
        }
        else{
            $mainCategory["image"] = "";
        }
        $mainCategory['url_key'] = $categories->getUrlKey();
        $allCategroy["root"] = $mainCategory;
        $childCategories = $this->cmsPlpPdp->getChildCategories($categories);
        $subcategories = $childCategories->getData();
        $i = 0;
        foreach ($subcategories as $category) { 
            $child = [];
            $subCategory1 = [];
            $categories1 = $this->category->create()->load($category['entity_id']);
            // checking include in menu and active 
            if(($categories1['include_in_menu'] != CmsPlpPdp::INCLUDE_IN_MENU) && ($categories1['is_active'] == CmsPlpPdp::ENABLED)){
                $child["id"] = $categories1->getId();
                $child["name"] = $categories1->getName();
                if($categories1->getImage() != Null){
                    $child["image"] = $this->urlHelper->getBaseUrl() . $categories1->getImage();
                }
                else{
                    $child["image"] = "";
                }
                $child["url_key"] = $categories1->getUrlKey();
                $child["request_path"] = $category['request_path'];
                $subcategories1 = $this->cmsPlpPdp->getChildCategories($categories1);
                foreach (($subcategories1->getData()) as $category1) {
                    $child1 = [];
                    $subCategory2 = [];
                    $categories2 = $this->category->create()->load($category1['entity_id']);
                    if(($categories2['include_in_menu'] != CmsPlpPdp::INCLUDE_IN_MENU) && $categories2['is_active'] == CmsPlpPdp::ENABLED){
                        $child1["id"] = $categories2->getId();
                        $child1["name"] = $categories2->getName();
                        if($categories2->getImage() != null){
                            $child1["image"] = $this->urlHelper->getBaseUrl() . $categories2->getImage();
                        }
                        else{
                            $child1["image"] = "";
                        }
                        $child1["url_key"] = $categories2->getUrlKey();
                        $child1["request_path"] = $category1['request_path'];
                        $subCategory1[] = $child1;
                    }
                }
                $child["level2"] = $subCategory1;
                array_push($subCategory, $child);
            }  
            
        }
        foreach($subCategory as $subCat){
            if($i != self::MAX_MENU_CATEGORY){
                $subCategorieList[] = $subCat;
                $i++;
            }
        }
        $mainCategory['level1'] = $subCategorieList;
        $allCategroy["root"] = $mainCategory;
        return $allCategroy;  
            
    }
}