<?php
namespace Swaminathan\About\Model;

use Swaminathan\About\Api\AboutInterface;
use Swaminathan\About\Model\CrudimageFactory as ImageFactory;
use Swaminathan\About\Model\Source\Visibility;
use Swaminathan\About\Model\Source\Status;
use Magento\Framework\App\Config\ScopeConfigInterface; 
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Sparsh\Banner\Api\Data\BannerInterface;
use Sparsh\Banner\Model\ResourceModel\Banner\CollectionFactory as BannerCollection;
use Sparsh\Banner\Model\ResourceModel\Banner\Collection as BannerCollectionData;
use Swaminathan\SparshBanner\Model\Banner as BannerData;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class AboutUs implements AboutInterface
{
    const PAGE_TITLE = 'about_us/about/page_title';

    const SHORT_DESCRIPTION = 'about_us/about/short_description';

    const TITLE = 'about_us/about/title';

    const INFO_SECTION_TITLE1 = 'about_us/about/info_section_title1';

    const INFO_SECTION_TITLE2 = 'about_us/about/info_section_title2';

    const ABOUTUS_IMAGE = 'about_us/about/about_us_image';

    const MOBILE_IMAGE = 'about_us/about/mobile_image';

    const DESCRIPTION = 'about_us/about/description';

    const META_TITLE = 'about_us/seo/meta_title';

    const META_KEYWORDS = 'about_us/seo/meta_keywords';

    const META_DESCRIPTION = 'about_us/seo/meta_description';

    const ZERO_TIME_ZONE = "0000-00-00 00:00:00";

    protected $imageFactory;

    protected $scopeConfig;

    protected $urlHelper;

    protected $TimezoneInterface;

    protected $bannerCollectionFactory;

    public function __construct(
        ImageFactory $imageFactory,
        ScopeConfigInterface $scopeConfig,
        UrlHelper $urlHelper,
        TimezoneInterface $timezoneInterface,
        BannerCollection $bannerCollectionFactory
    ) 
    {
        $this->imageFactory = $imageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->urlHelper = $urlHelper;
        $this->timezoneInterface = $timezoneInterface;
        $this->bannerCollectionFactory = $bannerCollectionFactory;
    }
    // Get About us content
    public function getAboutUsContent(){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $data = [];
        $productManufacturing = $this->getProductForManufacturing();
        $wholeSale = $this->getWholesaleDealers();
        $pageTitle = $this->scopeConfig->getValue(self::PAGE_TITLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($pageTitle) && $pageTitle == null){
            $pageTitle = "";
        }
        $shortDescription = $this->scopeConfig->getValue(self::SHORT_DESCRIPTION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($shortDescription) && $shortDescription == null){
            $shortDescription = "";
        }
        $title = $this->scopeConfig->getValue(self::TITLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($title) && $title == null){
            $title = "";
        }
        $infoSectionTitle1 = $this->scopeConfig->getValue(self::INFO_SECTION_TITLE1, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($infoSectionTitle1) && $infoSectionTitle1 == null){
            $infoSectionTitle1 = "";
        }
        $infoSectionTitle2 = $this->scopeConfig->getValue(self::INFO_SECTION_TITLE2, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($infoSectionTitle2) && $infoSectionTitle2 == null){
            $infoSectionTitle2 = "";
        }
        $aboutUsImage = $mediaUrl . 'aboutus/' . $this->scopeConfig->getValue(self::ABOUTUS_IMAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($this->scopeConfig->getValue(self::ABOUTUS_IMAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) && ($this->scopeConfig->getValue(self::ABOUTUS_IMAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == null)){
            $aboutUsImage = $this->urlHelper->getPlaceHolderImage();
        }
        $aboutUsMobileImage = $mediaUrl . 'aboutus/' . $this->scopeConfig->getValue(self::MOBILE_IMAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($this->scopeConfig->getValue(self::MOBILE_IMAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) && ($this->scopeConfig->getValue(self::ABOUTUS_IMAGE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == null)){
            $aboutUsMobileImage = $this->urlHelper->getPlaceHolderImage();
        }
        $description = $this->scopeConfig->getValue(self::DESCRIPTION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($description) && $description == null){
            $description = "";
        }
        // Meta title 
        $metaTitle = $this->scopeConfig->getValue(self::META_TITLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($metaTitle) && $metaTitle == null){
            $metaTitle = "";
        }
        // Meta keywords 
        $metaKeywords = $this->scopeConfig->getValue(self::META_KEYWORDS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($metaKeywords) && $metaKeywords == null){
            $metaKeywords = "";
        }
        // Meta descritpion 
        $metaDescription = $this->scopeConfig->getValue(self::META_DESCRIPTION, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if(empty($metaDescription) && $metaDescription == null){
            $metaDescription = "";
        }
        $data['meta_title'] = $metaTitle;
        $data['meta_keywords'] = $metaKeywords;
        $data['meta_description'] = $metaDescription;
        $data['page_title'] = $pageTitle;
        $data['short_description'] = $shortDescription;
        $data['about_us_banner'] = $this->getBanner();
        $data['title'] = $title;
        $data['about_us_image'] = $aboutUsImage;
        $data['about_us_mobile_image'] = $aboutUsMobileImage;
        $data['description'] = $description;
        $data['info_section_title1'] = $infoSectionTitle1;
        $data['info_section_title2'] = $infoSectionTitle2;
        $data['product_manufacturing'] = $productManufacturing;
        $data['wholesale_dealers'] = $wholeSale;
        $responseData[] = [
            "code" => 200,
            "status" => true,
            "data" => $data
        ];
        return $responseData;
    }
    
    public function getProductForManufacturing(){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $productManuFacturing = [];
        $productManuFacture = [];
        $about = $this->imageFactory->create()->getCollection();
        $about->addFieldToFilter('visibility', Visibility::PRODUCT_FOR_MANUFACTURING);
        $about->addFieldToFilter('status', Status::STATUS_ENABLED);
        $about = $about->getData();
        foreach($about as $aboutUs){
            $productManuFacturing['entity_id'] = $aboutUs['entity_id'];
            $productManuFacturing['title'] = $aboutUs['title'];
            $productManuFacturing['description'] = $aboutUs['description'];
            $productManuFacturing['alt_tag'] = $aboutUs['alt_tag'];
            $imageUrl = $mediaUrl . $aboutUs['image'];
            if(empty($aboutUs['image']) || $aboutUs['image'] == null){
                $imageUrl = $this->urlHelper->getPlaceHolderImage();
            }
            $productManuFacturing['image'] = $imageUrl;
            $productManuFacturing['status'] = $aboutUs['status'];
            $productManuFacturing['visibility'] = $aboutUs['visibility'];
            $productManuFacture[] = $productManuFacturing;

        }
        return $productManuFacture;
    }
    public function getWholesaleDealers(){
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $wholeSale = [];
        $wholeSaleDealers = [];
        $about = $this->imageFactory->create()->getCollection();
        $about->addFieldToFilter('visibility', Visibility::WHOLESALE_DEALERS);
        $about->addFieldToFilter('status', Status::STATUS_ENABLED);
        $about = $about->getData();
        foreach($about as $aboutUs){
            $wholeSale['entity_id'] = $aboutUs['entity_id'];
            $wholeSale['title'] = $aboutUs['title'];
            $wholeSale['description'] = $aboutUs['description'];
            $wholeSale['alt_tag'] = $aboutUs['alt_tag'];
            $imageUrl = $mediaUrl . $aboutUs['image'];
            if(empty($aboutUs['image']) || $aboutUs['image'] == null){
                $imageUrl = $this->urlHelper->getPlaceHolderImage();
            }
            $wholeSale['image'] = $imageUrl;
            $wholeSale['status'] = $aboutUs['status'];
            $wholeSale['visibility'] = $aboutUs['visibility'];
            $wholeSaleDealers[] = $wholeSale;

        }
        return $wholeSaleDealers;
    }

    public function getBanner()
    {
        $date = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $mediaUrl = $this->urlHelper->getMediaUrl();
        $bannerCollection = $this->bannerCollectionFactory->create();
        $bannerCollection->addFilter('is_active', 1);
        $bannerCollection->addFilter('visibility', BannerData::VISIBILE_ABOUTUS);
        $bannerCollection->getSelect()->group('banner_id');
        $bannerCollection->getSelect()->order(BannerInterface::POSITION, BannerCollectionData::SORT_ORDER_ASC);
        $bannerCollection->getData();
        $banners= [];
        $titleColor="";
        $buttonColor="";
        foreach ($bannerCollection as $banner) {
            $startDate = $banner->getStartDate();
            $endDate = $banner->getEndDate();
            if ((($startDate <= $date)   &&  ($endDate >= $date)) || (($startDate == self::ZERO_TIME_ZONE)  || ($endDate == self::ZERO_TIME_ZONE)) || ($startDate == ""  || ($endDate == "")) || (($startDate == NULL)  || ($endDate == NULL))){           
                if( $banner->getTitleColor() != null){
                    $titleColor = $banner->getTitleColor();
                }  
                if( $banner->getButtonColor() != null){
                $buttonColor = $banner->getButtonColor();
                }
                $mobileImage = $banner->getBannerImageMobile();
                $desktopImage = $banner->getBannerImage();
                $mobileImageUrl = $mediaUrl . $banner->getBannerImageMobile();
                $desktopImageUrl = $mediaUrl . $banner->getBannerImage();
                if(empty($mobileImage) && $mobileImage == null){
                    $mobileImageUrl = $this->urlHelper->getPlaceHolderImage();
                }
                if(empty($desktopImage) && $desktopImage == null){
                    $desktopImageUrl = $this->urlHelper->getPlaceHolderImage();
                }
                $singleBanner = [
                    'desktop' =>  $desktopImageUrl,
                    'mobile'  =>  $mobileImageUrl,
                    'content'  => $banner->getBannerTitle(),
                    'button_text'  => $banner->getLabelButtonText(),
                    'button_link'  => $banner->getCallToAction(),
                    'title_color'  => $titleColor,
                    'button_color'  => $buttonColor    
            ];
                $banners[] = $singleBanner;
            }
        }
        return $banners;
    }
}
