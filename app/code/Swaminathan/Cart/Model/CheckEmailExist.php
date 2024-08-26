<?php
namespace Swaminathan\Cart\Model; 

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Store\Model\StoreManagerInterface;

class CheckEmailExist implements \Swaminathan\Cart\Api\IsEmailAvailableInterface
{
    private $customerAccountManagement;

    private $storeManager;

    public function __construct
    (
        AccountManagementInterface $customerAccountManagement,
        StoreManagerInterface $storeManager,
    )
    {
        $this->customerAccountManagement = $customerAccountManagement;
        $this->storeManager = $storeManager;
    }

    // Check if the given email address is exist or not
    public function emailExistOrNot($customerEmail){
        if($customerEmail != "" ){
            $websiteId = (int)$this->storeManager->getWebsite()->getId();
            $isEmailNotExists = $this->customerAccountManagement->isEmailAvailable($customerEmail, $websiteId);
            if($isEmailNotExists == false){
                $response[] = [
                    'code' => 200,
                    'status' => true, 
                    'message' => "Email Address is already exists."
                ];
            }
            else{
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' => "Email Address doesn't exists."
                ];
            }
        }
        else{
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "Parameter Missing."
            ];
        }
        return $response;
    }
}
