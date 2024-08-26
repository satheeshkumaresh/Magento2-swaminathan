<?php
namespace Swaminathan\Customer\Model;
use Magento\Integration\Model\CustomerTokenService;

class CustomerRevokeTokenService implements \Swaminathan\Customer\Api\CustomerRevokeTokenServiceInterface
{
    /**
     * @var CustomerTokenService
     */
    private $customerTokenServiceFactory;
    
    public function __construct(
        
        CustomerTokenService $customerTokenServiceFactory
    ) {
        $this->_customerTokenServiceFactory = $customerTokenServiceFactory;
    }

   /**
     * Revoke Customer token
     *{@inheritdoc}
     * @param int $customerId
     * @return bool
     */
    
    
    public function revokeCustomerAccessToken($customerId)
    {
      return  $this->_customerTokenServiceFactory->revokeCustomerAccessToken($customerId);
           
    }

}