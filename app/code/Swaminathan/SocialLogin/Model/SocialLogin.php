<?php
namespace Swaminathan\SocialLogin\Model;

use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Mageplaza\SocialLogin\Model\Social;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Integration\Model\Oauth\TokenFactory;

class SocialLogin implements \Swaminathan\SocialLogin\Api\SocialLoginInterface 
{
    protected $customerRegistry;
    protected $customerDataFactory;
    protected $customerRepository;
    protected $social;
    protected $storeManager;
    protected $apiObject;
   

    public function __construct(
        CustomerRegistry $customerRegistry,
        Social $apiObject,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerRepositoryInterface $customerRepository,
        Social $social,
        StoreManagerInterface $storeManager,
        TokenFactory $tokenModelFactory
    ) {
        $this->_tokenModelFactory = $tokenModelFactory;
        $this->customerRegistry = $customerRegistry;
        $this->apiObject = $apiObject;
        $this->customerDataFactory = $customerDataFactory;
        $this->customerRepository = $customerRepository;
        $this->social = $social;
        $this->storeManager = $storeManager;
    }

    /**
         * Social Login
         *
         * @param mixed $data
         * @return string
     */
    
    public function socialLogin($data, $type = "Google"){
        $identifier =  (string)$data['sub'];
        $customer = $this->apiObject->getCustomerByEmail($data['email'], $this->storeManager->getStore()->getWebsiteId());
        if ($customer->getId()) {
            $title = "Login";
            $customerDataObj = $this->apiObject->load($customer->getId(), 'customer_id');
            if(empty($customerDataObj->getData())){
                $this->apiObject->setAuthorCustomer($identifier, $customer->getId(), $type);
            }
        } else {
            $title = "Signup";
            try {
                $datas = [];
                $datas['firstname'] =  $data['given_name'];
                $datas['lastname'] =  $data['family_name'];
                $datas['email'] =  $data['email'];
                $datas['type'] =  $type;
                $datas['password'] =  Null;
                $datas['identifier'] =  $data['sub'];
                $customer = $this->apiObject->createCustomerSocial($datas, $this->storeManager->getStore());
            } catch (Exception $e) {
                $response[] = [
                    'code' => 400, 
                    'status' => false, 
                    'message' => 'Email is Null, Please enter email in your profile', 
                    "redirect_url" => "customer/account/login"
                ];
            }
        }
        // Generate customer access token by customer id
        $customerToken = $this->_tokenModelFactory->create();
        $tokenKey = $customerToken
            ->createCustomerToken($customer->getId())
            ->getToken();
        // Get customer data by customer repository
        $customerModel = $this->customerRepository->getById($customer->getId());
        $customerData = $customerModel->__toArray();
        $response[] = [
            'code' => 200, 
            'status' => true , 
            'title' => $title,
            'message' => 'Logged In Successfully', 
            'token' => $tokenKey, 
            'customer_data' => $customerData
        ];
        return $response;
    }
}
