<?php
namespace Swaminathan\Customer\Model;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Integration\Model\Oauth\TokenFactory;

class CustomerToken implements \Swaminathan\Customer\Api\CustomerTokenInterface
{
    public function __construct(
        CustomerRepositoryInterface $CustomerRepositoryInterface,
        TokenFactory $TokenFactory
    ) {
        $this->CustomerRepositoryInterface = $CustomerRepositoryInterface;
        $this->TokenFactory = $TokenFactory;
    }

    /**
    * 
    * @param int $customerId
    * @return array
    */

    public function customerToken($customerId)
    {
        $tokenModel = $this->TokenFactory->create();
        $customerToken = $tokenModel
                        ->createCustomerToken($customerId)
                        ->getToken();
        return $customerToken;
       
    }
}
