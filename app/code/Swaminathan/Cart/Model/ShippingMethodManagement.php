<?php
namespace Swaminathan\Cart\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\EstimateAddressInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote\Address as QuoteAddressResource;
use Magento\Customer\Model\Data\Address as CustomerAddress;

class ShippingMethodManagement 
    implements \Swaminathan\Cart\Api\ShippingMethodManagementInterface
{
    /**
     * Quote repository model
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Shipping method converter
     *
     * @var ShippingMethodConverter
     */
    protected $converter;

    /**
     * Customer Address repository
     *
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var DataObjectProcessor $dataProcessor
     */
    private $dataProcessor;

    protected $quote;

    /**
     * @var AddressInterfaceFactory $addressFactory
     */
    private $addressFactory;

    /**
     * @var QuoteAddressResource
     */
    private $quoteAddressResource;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Constructor
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param ShippingMethodConverter $converter
     * @param AddressRepositoryInterface $addressRepository
     * @param Quote\TotalsCollector $totalsCollector
     * @param Quote $quote
     * @param AddressInterfaceFactory|null $addressFactory
     * @param QuoteAddressResource|null $quoteAddressResource
     * @param CustomerSession|null $customerSession
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        ShippingMethodConverter $converter,
        AddressRepositoryInterface $addressRepository,
        TotalsCollector $totalsCollector,
        Quote $quote,
        AddressInterfaceFactory $addressFactory = null,
        QuoteAddressResource $quoteAddressResource = null,
        CustomerSession $customerSession = null
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->converter = $converter;
        $this->addressRepository = $addressRepository;
        $this->totalsCollector = $totalsCollector;
        $this->quote = $quote;
        $this->addressFactory = $addressFactory ?: ObjectManager::getInstance()
            ->get(AddressInterfaceFactory::class);
        $this->quoteAddressResource = $quoteAddressResource ?: ObjectManager::getInstance()
            ->get(QuoteAddressResource::class);
        $this->customerSession = $customerSession ?? ObjectManager::getInstance()->get(CustomerSession::class);
    }

    /**
     * @inheritDoc
     */
    public function get($cartId)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        /** @var Address $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getCountryId()) {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The shipping address is missing. Set the address and try again."
            ];
            return $response;
        }

        $shippingMethod = $shippingAddress->getShippingMethod();
        if (!$shippingMethod) {
            return null;
        }

        $shippingAddress->collectShippingRates();
        /** @var Rate $shippingRate */
        $shippingRate = $shippingAddress->getShippingRateByCode($shippingMethod);
        if (!$shippingRate) {
            return null;
        }
        return $this->converter->modelToDataObject($shippingRate, $quote->getQuoteCurrencyCode());
    }

    /**
     * @inheritDoc
     */
    public function getList($cartId)
    {
        $output = [];
        $shippingMethods = [];
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getCountryId()) {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The shipping address is missing. Set the address and try again."
            ];
            return $response;
        }
        $shippingAddress->collectShippingRates();
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $output[] = $this->converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }
        foreach($output as $shippingMethodData){
            $shippingMethodDatas['carrier_code'] = $shippingMethodData->getCarrierCode();
            $shippingMethodDatas['method_code'] = $shippingMethodData->getMethodCode();
            $shippingMethodDatas['carrier_title'] = $shippingMethodData->getCarrierTitle();
            $shippingMethodDatas['method_title'] = $shippingMethodData->getMethodTitle();
            $shippingMethodDatas['amount'] = $shippingMethodData->getAmount();
            $shippingMethodDatas['base_amount'] = $shippingMethodData->getBaseAmount();
            $shippingMethodDatas['available'] = $shippingMethodData->getAvailable();
            $shippingMethodDatas['error_message'] = $shippingMethodData->getErrorMessage();
            $shippingMethodDatas['price_excl_tax'] = $shippingMethodData->getPriceExclTax();
            $shippingMethodDatas['price_incl_tax'] = $shippingMethodData->getPriceInclTax();
            $shippingMethods[] = $shippingMethodDatas;
        }
        $response[] = [
            'code' => 200,
            'status' => true, 
            'data' => $shippingMethods
        ];
        return $response;
    }

    /**
     * @inheritDoc
     */
    public function set($cartId, $carrierCode, $methodCode)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        try {
            $this->apply($cartId, $carrierCode, $methodCode);
        } catch (\Exception $e) {
            throw $e;
        }

        try {
            $this->quoteRepository->save($quote->collectTotals());
        } catch (\Exception $e) {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The shipping method can\'t be set"
            ];
            return $response;
        }
        return true;
    }

    /**
     * Apply carrier code.
     *
     * @param int $cartId The shopping cart ID.
     * @param string $carrierCode The carrier code.
     * @param string $methodCode The shipping method code.
     * @return void
     * @throws InputException The shipping method is not valid for an empty cart.
     * @throws NoSuchEntityException CThe Cart includes virtual product(s) only, so a shipping address is not used.
     * @throws StateException The billing or shipping address is not set.
     * @throws \Exception
     */
    public function apply($cartId, $carrierCode, $methodCode)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (0 == $quote->getItemsCount()) {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The shipping method can\'t be set for an empty cart. Add an item to cart and try again."
            ];
            return $response;
        }
        if ($quote->isVirtual()) {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The Cart includes virtual product(s) only, so a shipping address is not used."
            ];
            return $response;
        }
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getCountryId()) {
            // Remove empty quote address
            $this->quoteAddressResource->delete($shippingAddress);
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The shipping address is missing. Set the address and try again."
            ];
            return $response;
        }
        $shippingMethod = $carrierCode . '_' . $methodCode;
        $shippingAddress->setShippingMethod($shippingMethod);
        $shippingAssignments = (array)$quote->getExtensionAttributes()->getShippingAssignments();
        if (!empty($shippingAssignments)) {
            $shippingAssignment = $shippingAssignments[0];
            $shipping = $shippingAssignment->getShipping();
            $shipping->setMethod($shippingMethod);
            $shippingAssignment->setShipping($shipping);
        }
    }

    /**
     * @inheritDoc
     */
    public function estimateByAddress($cartId, EstimateAddressInterface $address)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        return $this->getShippingMethods($quote, $address);
    }

    /**
     * @inheritdoc
     */
    public function estimateByExtendedAddress($cartId, AddressInterface $address)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }
        return $this->getShippingMethods($quote, $address);
    }

    /**
     * @inheritDoc
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function estimateByAddressId($cartId, $addressId)
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }
        $address = $this->getAddress($addressId, $quote);

        return $this->getShippingMethods($quote, $address);
    }

    /**
     * Get estimated rates
     *
     * @param Quote $quote
     * @param int $country
     * @param string $postcode
     * @param int $regionId
     * @param string $region
     * @param ExtensibleDataInterface|null $address
     * @return ShippingMethodInterface[] An array of shipping methods.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 100.1.6
     */
    protected function getEstimatedRates(
        Quote $quote,
        $country,
        $postcode,
        $regionId,
        $region,
        $address = null
    ) {
        if (!$address) {
            $address = $this->addressFactory->create()
                ->setCountryId($country)
                ->setPostcode($postcode)
                ->setRegionId($regionId);
        }
        return $this->getShippingMethods($quote, $address);
    }

    /**
     * Get list of available shipping methods
     *
     * @param Quote $quote
     * @param ExtensibleDataInterface $address
     * @return ShippingMethodInterface[]
     */
    private function getShippingMethods(Quote $quote, $address)
    {
        $output = [];
        $shippingMethods = [];
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($this->extractAddressData($address));
        $shippingAddress->setCollectShippingRates(true);

        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
        $quoteCustomerGroupId = $quote->getCustomerGroupId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();
        $isCustomerGroupChanged = $quoteCustomerGroupId !== $customerGroupId;
        if ($isCustomerGroupChanged) {
            $quote->setCustomerGroupId($customerGroupId);
        }
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $output[] = $this->converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }
        if ($isCustomerGroupChanged) {
            $quote->setCustomerGroupId($quoteCustomerGroupId);
        }
        foreach($output as $shippingMethodData){
            $shippingMethodDatas['carrier_code'] = $shippingMethodData->getCarrierCode();
            $shippingMethodDatas['method_code'] = $shippingMethodData->getMethodCode();
            $shippingMethodDatas['carrier_title'] = $shippingMethodData->getCarrierTitle();
            $shippingMethodDatas['method_title'] = $shippingMethodData->getMethodTitle();
            $shippingMethodDatas['amount'] = $shippingMethodData->getAmount();
            $shippingMethodDatas['base_amount'] = $shippingMethodData->getBaseAmount();
            $shippingMethodDatas['available'] = $shippingMethodData->getAvailable();
            $shippingMethodDatas['error_message'] = $shippingMethodData->getErrorMessage();
            $shippingMethodDatas['price_excl_tax'] = $shippingMethodData->getPriceExclTax();
            $shippingMethodDatas['price_incl_tax'] = $shippingMethodData->getPriceInclTax();
            $shippingMethods[] = $shippingMethodDatas;
        }
        $response[] = [
            'code' => 200,
            'status' => true, 
            'data' => $shippingMethods
        ];
        return $response;
    }

    /**
     * Get transform address interface into Array
     *
     * @param ExtensibleDataInterface $address
     * @return array
     */
    private function extractAddressData($address)
    {
        $className = \Magento\Customer\Api\Data\AddressInterface::class;
        if ($address instanceof AddressInterface) {
            $className = AddressInterface::class;
        } elseif ($address instanceof EstimateAddressInterface) {
            $className = EstimateAddressInterface::class;
        }

        $addressData = $this->getDataObjectProcessor()->buildOutputDataArray(
            $address,
            $className
        );
        unset($addressData[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);

        return $addressData;
    }

    /**
     * Gets the data object processor
     *
     * @return DataObjectProcessor
     * @deprecated 101.0.0
     */
    private function getDataObjectProcessor()
    {
        if ($this->dataProcessor === null) {
            $this->dataProcessor = ObjectManager::getInstance()
                ->get(DataObjectProcessor::class);
        }
        return $this->dataProcessor;
    }

    /**
     * Gets the address if exists for customer
     *
     * @param int $addressId
     * @param Quote $quote
     * @return CustomerAddress
     * @throws InputException The shipping address is incorrect.
     */
    private function getAddress(int $addressId, Quote $quote): CustomerAddress
    {
        $addresses = $quote->getCustomer()->getAddresses();
        foreach ($addresses as $address) {
            if ($addressId === (int)$address->getId()) {
                return $address;
            }
        }
        $response[] = [
            'code' => 200,
            'status' => true, 
            'message' =>"The shipping address is missing. Set the address and try again."
        ];
        return $response;
    }
}

