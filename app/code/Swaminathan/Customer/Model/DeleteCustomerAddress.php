<?php
namespace Swaminathan\Customer\Model;

use Exception;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Customer\Api\AddressRepositoryInterface;

class DeleteCustomerAddress
{
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    public function __construct(
        AddressRepositoryInterface $addressRepository,
        Request $request
    ) {
        $this->addressRepository = $addressRepository;
        $this->_request= $request;
    }

    /**
     * 
     *@param int $addressId
     * @return bool
     */
    public function deleteCustomerAddressById($addressId)
    {
        //$addressId = $this->_request->getParam('addressId');
        //return  $addressId;
        
        $error = true;
        try {
            $this->addressRepository->deleteById($addressId);
        } catch (Exception $e) {
            $error = false;  
            echo "failed";     
        }
        return $error;
    }
}