<?php
namespace Swaminathan\SparshBanner\Plugin;

use Sparsh\Banner\Model\BannerFactory;
use Sparsh\Banner\Model\ResourceModel\Banner;
use Sparsh\Banner\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
class SaveBanner
{
    
    protected $bannerFactory;

    protected $timezoneInterface;

    protected $bannerResource;

    protected $bannerCollectionFactory;
    
    public function __construct(
        BannerFactory $bannerFactory,
        Banner $bannerResource,
        CollectionFactory $bannerCollectionFactory,
        TimezoneInterface $timezoneInterface
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->bannerResource = $bannerResource;
        $this->bannerCollectionFactory = $bannerCollectionFactory;
        $this->timezoneInterface = $timezoneInterface;
    }
    
    public function afterexecute(
        \Sparsh\Banner\Controller\Adminhtml\Banner\Save $subject,
    )
    {
        $nowDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $getPost = $subject->getRequest()->getPost(); 
        $bannerStartDate = $getPost['post']['start_date'];
        $bannerEndDate = $getPost['post']['end_date'];
        $bannerId = $subject->getRequest()->getParam('banner_id');
        if(isset($bannerId)){
            $bannerData = $this->bannerFactory->create()->load($bannerId);
            $bannerEditForm = $bannerData->getData();
            if ($bannerEditForm['start_date'] !='') {
                $startDate=date_create($bannerStartDate);
                $startDate=date_format($startDate, "Y-m-d H:i:s");
                $bannerData->setStartDate($startDate);
            }
            if($bannerStartDate == ""){
                $bannerData->setStartDate("");
            }
            if ($bannerEditForm['end_date'] !='') {
                $endDate=date_create($bannerEndDate);
                $endDate=date_format($endDate, "Y-m-d H:i:s");
                $bannerData->setEndDate($endDate);
            }
            if($bannerEndDate == ""){
                $bannerData->setEndDate("");
            }
            $bannerData->setData('update_time',date('Y-m-d H:i:s'));
            $bannerData->save();
        }
        else{
            $requestData = $subject->getRequest()->getPost()['post'];
            $bannerModels = $this->bannerFactory->create()->getCollection()->getLastItem();
            $bannerGetModels = $bannerModels->getData();
            $newBannerId = $bannerGetModels['banner_id'];
            $bannerModel = $this->bannerFactory->create()->load($newBannerId, 'banner_id');
            $bannerModelFormData = $bannerModel->getData();
            if ($bannerModelFormData['start_date'] !='') {
                $startDate=date_create($requestData['start_date']);
                $startDate=date_format($startDate, "Y-m-d H:i:s");
                $bannerModel->setStartDate($startDate);
            }
            if($requestData['start_date'] == ""){
                $bannerModel->setStartDate("");
            }
            if ($bannerModelFormData['end_date'] !='') {
                $endDate=date_create($requestData['end_date']);
                $endDate=date_format($endDate, "Y-m-d H:i:s");
                $bannerModel->setEndDate($endDate);
            }
            if($requestData['end_date'] == ""){
                $bannerModel->setEndDate("");
            }
            $bannerModel->setCreationTime(date('Y-m-d H:i:s'));
            $bannerModel->setUpdateTime(date('Y-m-d H:i:s'));
            $bannerModel->save();   
        }
    }
    
}