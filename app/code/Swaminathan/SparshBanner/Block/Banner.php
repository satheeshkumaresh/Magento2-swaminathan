<?php
 
namespace Swaminathan\SparshBanner\Block;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Sparsh\Banner\Api\Data\BannerInterface;
use Sparsh\Banner\Model\ResourceModel\Banner\Collection as BannerCollection;
use Swaminathan\SparshBanner\Model\Banner as BannerData;

class Banner extends \Sparsh\Banner\Block\Banner
{
    const ZERO_TIME_ZONE = "0000-00-00 00:00:00";
    public function getConfig($config)
    {
        return $this->bannerHelper->getConfig($config);
    }

    public function getBanner()
    {
        if (!$this->hasData('banner')) {
            $date = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            
            $bannerCollection = $this->bannerCollectionFactory->create();
            $bannerCollection->addFilter('is_active', 1);
            $bannerCollection->addFilter('visibility', BannerData::VISIBILE_HOMEPAGE);
            $bannerCollection->getSelect()->group('banner_id');
            $bannerCollection->getSelect()->order(BannerInterface::POSITION, BannerCollection::SORT_ORDER_ASC);
            $bannerCollection->getData();
            $banners= [];
            $titleColor="";
            $buttonColor="";
            $Alttag = "";
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
                    if($banner->getAltTag() != null){
                         $Alttag = $banner->getAltTag();
                      }
                        
                    $singleBanner = [
                        'desktop' => $banner->getBannerImage(),
                        'mobile'  => $banner->getBannerImageMobile(),
                        'content'  => $banner->getBannerTitle(),
                        'button_text'  => $banner->getLabelButtonText(),
                        'button_link'  => $banner->getCallToAction(),
                        'title_color'  => $titleColor,
                        'button_color' => $buttonColor,
                        'alt_tag'      =>  $Alttag

                        
                ];
                    $banners[] = $singleBanner;
                }
            }
        }
        return $banners;
    }
}
