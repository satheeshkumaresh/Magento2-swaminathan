<?php
namespace Swaminathan\Cart\Model;

use Magento\Directory\Model\ResourceModel\Region\Collection  as RegionCollection;

class ShippingBillingAddress implements \Swaminathan\Cart\Api\ShippingBillingAddresstInterface
{
    protected $_customerFactory;
    protected $_addressFactory;
    protected $countryFactory;
    protected $regionCollection;
    public function __construct
    (
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        RegionCollection $regionCollection
    )
    {
        $this->_customerFactory = $customerFactory;
        $this->_addressFactory = $addressFactory;
        $this->countryFactory = $countryFactory;
        $this->regionCollection = $regionCollection;
    }

    // Get Shipping & billing address by customer id
    public function getShippingBillingAddress($customerId){
        if($customerId != "" ){
            $billingAddressData = [];
            $shippingAddressData = [];
            // Load customer factory by customer id
            $customer = $this->_customerFactory->create()->load($customerId);
            // Get default billing address
            $billingAddressId = $customer->getDefaultBilling();
             // load billing address by address id
            $billingAddress = $this->_addressFactory->create()->load($billingAddressId);
            $billingAddressDatas = $billingAddress->getData();
            
            if(!empty($billingAddressDatas)){
                $billingAddressData['name'] = $billingAddressDatas['firstname'] . $billingAddressDatas['lastname'];
                $billingAddressData['company'] = $billingAddressDatas['company'];
                $streetAddress = [];
                $street = explode("\n",$billingAddressDatas['street']);
                foreach($street as $streetName){
                    $streetAddress[] = $streetName;
                }
                $billingAddressData['street_name'] = $streetAddress;
                $billingAddressData['city'] = $billingAddressDatas['city'];
                $billingAddressData['region'] = $billingAddressDatas['region'];
                $billingAddressData['telephone'] = $billingAddressDatas['telephone'];
                $billingAddressData['post_code'] = $billingAddressDatas['postcode'];
                $billingAddressData['country_name'] = $this->getCountryname($billingAddressDatas['country_id']);
            }
            // Get default shipping address
            $shippingAddressId = $customer->getDefaultShipping();
            // load shipping address by address id
            $shippingAddress = $this->_addressFactory->create()->load($shippingAddressId);
            $shippingAddressDatas = $shippingAddress->getData();
            if(!empty($shippingAddressDatas)){
                $shippingAddressData['name'] = $shippingAddress['firstname'] . $shippingAddress['lastname'];
                $shippingAddressData['company'] = $shippingAddress['company'];
                $streetAddress = [];
                $street = explode("\n",$shippingAddress['street']);
                foreach($street as $streetName){
                    $streetAddress[] = $streetName;
                }
                $shippingAddressData['street_name'] = $streetAddress;
                $shippingAddressData['city'] = $shippingAddress['city'];
                $shippingAddressData['region'] = $shippingAddress['region'];
                $shippingAddressData['telephone'] = $shippingAddress['telephone'];
                $shippingAddressData['post_code'] = $shippingAddress['postcode'];
                $shippingAddressData['country_name'] = $this->getCountryname($shippingAddress['country_id']);
            }
            $address['billing_address'] = $billingAddressData;
            $address['shipping_address'] = $shippingAddressData;
            if(!empty($billingAddressData) || !empty($shippingAddressData)){
                $response[] = [
                    'code' => 200,
                    'status' => true, 
                    'data' => $address
                ];
            }
            else{
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' => "Shipping & Billing address is not found."
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
    // Get Country name by Country Code
    public function getCountryname($countryCode){    
        $country = $this->countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }
    // get region name by id
    public function getRegionNameById(int $id)
    {
        $region = $this->regionCollection->getItemById($id);
        return $region->getCode();
    }
}
