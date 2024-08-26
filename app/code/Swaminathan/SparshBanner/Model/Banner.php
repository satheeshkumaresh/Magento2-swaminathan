<?php
namespace Swaminathan\SparshBanner\Model;

use Sparsh\Banner\Api\Data\BannerInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Banner extends AbstractModel implements BannerInterface, IdentityInterface
{
    /**
     * Base media path for banner's image
     */
    const BASE_IMAGE_MEDIA_PATH = 'sparsh/banner/image';

    /**
     * Base media path for banner's image
     */
    const BASE_VIDEO_MEDIA_PATH = 'sparsh/banner/video';

    /**
     * Banner's Status enabled
     */
    const STATUS_ENABLED = 1;

    /**
     *Banner's Status disabled
     */
    const STATUS_DISABLED = 0;

     /**
     * Banner's Visibility for home page
     */
    const VISIBILE_HOMEPAGE = 1;

    /**
     *Banner's Visibility for about us page
     */
    const VISIBILE_ABOUTUS = 0;

    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'sparsh_banner';

    /**
     * Cache tag
     *
     * @var string
     */
    protected $cacheTag = 'sparsh_banner';

    /**
     * Prefix of model banner names
     *
     * @var string
     */
    protected $eventPrefix = 'sparsh_banner';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sparsh\Banner\Model\ResourceModel\Banner::class);
    }

    /**
     * Retrive Model Title
     *
     * @param boolean $plural plural
     *
     * @return stringcreation_time
     */
    public function getOwnTitle($plural = false)
    {
        return $plural ? 'Banners' : 'Banner';
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get banner ID
     *
     * @return int|null
     */
    public function getBannerId()
    {
        return $this->getData(self::BANNER_ID);
    }

    /**
     * Get banner title
     *
     * @return string|null
     */
    public function getBannerTitle()
    {
        return $this->getData(self::BANNER_TITLE);
    }

    /**
     * Get banner description
     *
     * @return string|null
     */
    public function getBannerDescription()
    {
        return $this->getData(self::BANNER_DESCRIPTION);
    }

    /**
     * Get banner type
     *
     * @return string|null
     */
    public function getBannerType()
    {
        return $this->getData(self::BANNER_TYPE);
    }

    /**
     * Get banner image
     *
     * @return string|null
     */
    public function getBannerImage()
    {

        return $this->getData(self::BANNER_IMAGE);
    }

    /**
     * Get banner video
     *
     * @return string|null
     */
    public function getBannerVideo()
    {

        return $this->getData(self::BANNER_VIDEO);
    }

    /**
     * Get banner youtube
     *
     * @return string|null
     */
    public function getBannerYoutube()
    {

        return $this->getData(self::BANNER_YOUTUBE);
    }

    /**
     * Get banner vimeo
     *
     * @return string|null
     */
    public function getBannerVimeo()
    {

        return $this->getData(self::BANNER_VIMEO);
    }
    /**
     * Get banner video autoplay
     *
     * @return string|null
     */
    public function getBannerVideoAutoplay()
    {

        return $this->getData(self::BANNER_VIDEO_AUTOPLAY);
    }
    /**
     * Get banner video image thumb
     *
     * @return string|null
     */
    public function getBannerVideoThumbImage()
    {
        return $this->getData(self::BANNER_VIDEO_THUMB_IMAGE);
    }
    /**
     * Retrieve true if banner is active
     *
     * @return boolean [description]
     */
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_ENABLED => __('Enabled'),
            self::STATUS_DISABLED => __('Disabled')
        ];
    }

    /**
     * Banner Visibility
     *
     * @return boolean [description]
     */
    public function getAvailableVisibilities()
    {
        return [
            self::VISIBILE_HOMEPAGE => __('Home Page'),
            self::VISIBILE_ABOUTUS => __('Aboutus Page')
        ];
    }

    /**
     * Get button text for action of banner
     *
     * @return string|null
     */
    public function getLabelButtonText()
    {
        return $this->getData(self::LABEL_BUTTON_TEXT);
    }

    /**
     * Get action of banner button
     *
     * @return string|null
     */
    public function getCallToAction()
    {
        return $this->getData(self::CALL_TO_ACTION);
    }

    /**
     * Get banner position
     *
     * @return int|null
     */
    public function getPosition()
    {
        return $this->getData(self::POSITION);
    }

    /**
     * Get start date
     *
     * @return string|null
     */
    public function getStartDate()
    {
        return $this->getData(self::START_DATE);
    }

    /**
     * Get end date
     *
     * @return string|null
     */
    public function getEndDate()
    {
        return $this->getData(self::END_DATE);
    }

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreationTime()
    {
        return $this->getData(self::CREATION_TIME);
    }

    /**
     * Get update time
     *
     * @return string|null
     */
    public function getUpdateTime()
    {
        return $this->getData(self::UPDATE_TIME);
    }

    /**
     * Is active
     *
     * @return bool|null
     */
    public function isActive()
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * Set banner id
     *
     * @param int $bannerId bannerId
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setBannerId($bannerId)
    {
        return $this->setData(self::BANNER_ID, $bannerId);
    }

    /**
     * Set banner title
     *
     * @param string $bannerTitle bannerTitle
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setBannerTitle($bannerTitle)
    {
        return $this->setData(self::BANNER_TITLE, $bannerTitle);
    }

    /**
     * Set banner description
     *
     * @param string $bannerDescription bannerDescription
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setBannerDescription($bannerDescription)
    {
        return $this->setData(self::BANNER_DESCRIPTION, $bannerDescription);
    }

    /**
     * Set banner type
     *
     * @param string $bannerType bannerType
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setBannerType($bannerType)
    {
        return $this->setData(self::BANNER_TYPE, $bannerType);
    }

    /**
     * Set banner image
     *
     * @param string $bannerImage bannerImage
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setBannerImage($bannerImage)
    {
        return $this->setData(self::BANNER_IMAGE, $bannerImage);
    }

    /**
     * Set banner video
     *
     * @param string $bannerVideo bannerVideo
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setBannerVideo($bannerVideo)
    {
        return $this->setData(self::BANNER_VIDEO, $bannerVideo);
    }

    /**
     * Set banner youtube
     *
     * @param string $bannerYoutube bannerYoutube
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setBannerYoutube($bannerYoutube)
    {
        return $this->setData(self::BANNER_YOUTUBE, $bannerYoutube);
    }
     /**
      * Set banner vimeo
      *
      * @param string $bannerVimeo bannerVimeo
      *
      * @return \Sparsh\Banner\Api\Data\BannerInterface
      */
    public function setBannerVimeo($bannerVimeo)
    {
        return $this->setData(self::BANNER_VIMEO, $bannerVimeo);
    }
    /**
     * Set banner video autoplay
     *
     * @param string $bannerVideoAutoplay bannerVideoAutoplay
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setBannerVideoAutoplay($bannerVideoAutoplay)
    {
        return $this->setData(self::BANNER_VIDEO_AUTOPLAY, $bannerVideoAutoplay);
    }
     /**
      * Set banner video thumb image
      *
      * @param string $bannerVideoThumbImage bannerVideoThumbImage
      *
      * @return \Sparsh\Banner\Api\Data\BannerInterface
      */
    public function setBannerVideoThumbImage($bannerVideoThumbImage)
    {
        return $this->setData(self::BANNER_VIDEO_THUMB_IMAGE, $bannerVideoThumbImage);
    }
    /**
     * Set button text
     *
     * @param string $labelbuttonText labelbuttonText
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setLabelButtonText($labelbuttonText)
    {
        return $this->setData(self::LABEL_BUTTON_TEXT, $labelbuttonText);
    }

    /**
     * Set  calltoaction
     *
     * @param string $calltoAction calltoAction
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setCallToAction($calltoAction)
    {
        return $this->setData(self::CALL_TO_ACTION, $calltoAction);
    }

    /**
     * Set banner position
     *
     * @param int|null $position position
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setPosition($position)
    {
        return $this->setData(self::POSITION, $position);
    }

    /**
     * Set start date
     *
     * @param string $start_date start_date
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setStartDate($start_date)
    {
        return $this->setData(self::START_DATE, $start_date);
    }

    /**
     * Set end date
     *
     * @param string $end_date end_date
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setEndDate($end_date)
    {
        return $this->setData(self::END_DATE, $end_date);
    }

    /**
     * Set event creation time
     *
     * @param string $creation_time creation_time
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setCreationTime($creation_time)
    {
        return $this->setData(self::CREATION_TIME, $creation_time);
    }

    /**
     * Set update time
     *
     * @param string $update_time update_time
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setUpdateTime($update_time)
    {
        return $this->setData(self::UPDATE_TIME, $update_time);
    }

    /**
     * Set is active
     *
     * @param int|bool $is_active is_active
     *
     * @return \Sparsh\Banner\Api\Data\BannerInterface
     */
    public function setIsActive($is_active)
    {
        return $this->setData(self::IS_ACTIVE, $is_active);
    }

    public function getStores()
    {

        return $this->hasData('stores') ? $this->getData('stores') : (array)$this->getData('store_id');
    }

    public function getCustomerGroup()
    {
        return  (array)$this->getData('customer_group_id');
    }
}
