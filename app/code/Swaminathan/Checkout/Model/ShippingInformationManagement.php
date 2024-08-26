<?php
namespace Swaminathan\Checkout\Model;

use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Swaminathan\Checkout\Api\Data\ShippingInformationInterface;
use Swaminathan\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\QuoteAddressValidator;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Psr\Log\LoggerInterface as Logger;
use Magento\Checkout\Model\PaymentDetailsFactory;
use Swaminathan\Cart\Helper\Data as DataHelper;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Magento\Quote\Model\Quote\ItemFactory;
use Swaminathan\Checkout\Helper\Data as TotalSummary;

class ShippingInformationManagement implements \Swaminathan\Checkout\Api\ShippingInformationManagementInterface
{

    protected $totalSummary;

    protected $itemFactory;

    protected $productHelper;

    protected $paymentMethodManagement;

    protected $dataHelper;

    protected $paymentDetailsFactory;

    protected $cartTotalsRepository;

    protected $quoteRepository;
    
    protected $logger;

    protected $addressValidator;

    protected $addressRepository;

    protected $scopeConfig;

    protected $totalsCollector;

    private $cartExtensionFactory;

    protected $shippingAssignmentFactory;

    private $shippingFactory;

    public function __construct(
        TotalSummary $totalSummary,
        ItemFactory $itemFactory,
        ProductHelper $productHelper,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentDetailsFactory $paymentDetailsFactory,
        CartTotalRepositoryInterface $cartTotalsRepository,
        CartRepositoryInterface $quoteRepository,
        QuoteAddressValidator $addressValidator,
        Logger $logger,
        AddressRepositoryInterface $addressRepository,
        ScopeConfigInterface $scopeConfig,
        TotalsCollector $totalsCollector,
        DataHelper $dataHelper,
        CartExtensionFactory $cartExtensionFactory = null,
        ShippingAssignmentFactory $shippingAssignmentFactory = null,
        ShippingFactory $shippingFactory = null
    ) {
        $this->totalSummary = $totalSummary;
        $this->itemFactory = $itemFactory;
        $this->productHelper = $productHelper;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->quoteRepository = $quoteRepository;
        $this->addressValidator = $addressValidator;
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
        $this->totalsCollector = $totalsCollector;
        $this->dataHelper = $dataHelper;
        $this->cartExtensionFactory = $cartExtensionFactory ?: ObjectManager::getInstance()
            ->get(CartExtensionFactory::class);
        $this->shippingAssignmentFactory = $shippingAssignmentFactory ?: ObjectManager::getInstance()
            ->get(ShippingAssignmentFactory::class);
        $this->shippingFactory = $shippingFactory ?: ObjectManager::getInstance()
            ->get(ShippingFactory::class);
    }

    /**
     * Save address information.
     *
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function saveAddressInformation(
        $cartId,
        ShippingInformationInterface $addressInformation
    ){
         /** @var \Magento\Quote\Model\Quote $quote */
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $this->validateQuote($quote);
        $address = $addressInformation->getShippingAddress();
        $this->validateAddress($address);

        if (!$address->getCustomerAddressId()) {
            $address->setCustomerAddressId(null);
        }

        try {
            $billingAddress = $addressInformation->getBillingAddress();
            if ($billingAddress) {
                if (!$billingAddress->getCustomerAddressId()) {
                    $billingAddress->setCustomerAddressId(null);
                }
                $this->addressValidator->validateForCart($quote, $billingAddress);
                $quote->setBillingAddress($billingAddress);
            }

            $this->addressValidator->validateForCart($quote, $address);
            $addressFrom = $addressInformation->getAddressFrom();
            $carrierCode = $addressInformation->getShippingCarrierCode();
            $address->setLimitCarrier($carrierCode);
            $methodCode = $addressInformation->getShippingMethodCode();
            $quote = $this->prepareShippingAssignment($quote, $address, $carrierCode . '_' . $methodCode);

            $quote->setIsMultiShipping(false);
            $quote->setAddressFrom($addressFrom);
            $this->quoteRepository->save($quote);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The shipping information was unable to be saved. "
            ];
            return $response;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The shipping information was unable to be saved. Verify the input data and try again."
            ];
            return $response;
        }

        $shippingAddress = $quote->getShippingAddress();

        if (!$quote->getIsVirtual()
            && !$shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod())
        ) {
            $errorMessage = $methodCode ?
                __('Carrier with such method not found: %1, %2', $carrierCode, $methodCode)
                : __('The shipping method is missing. Select the shipping method and try again.');
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => $errorMessage
            ];
            return $response;
        }
        $data = [];
        $totalSegment = [];
        /** @var PaymentDetailsInterface $paymentDetails */
        $paymentDetails = $this->paymentDetailsFactory->create();
        $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
        $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));
        $data = $this->totalSummary->getSummaryTotal($paymentDetails);
        $response[] = [
            'code' => 200,
            'status' => true, 
            'data' => $data
        ];
        return $response;
    }
    
    public function getPaymentMethod($cartId){
        $paymentMethods = [];
        foreach ($this->paymentMethodManagement->getList($cartId) as $paymentMethod) {
            $paymentMethods[] = [
                'code' => $paymentMethod->getCode(),
                'title' => $paymentMethod->getTitle()
            ];
        }
        return $paymentMethods;
    }
    /**
     * Validate shipping address
     *
     * @param AddressInterface|null $address
     * @return void
     * @throws StateException
     */
    private function validateAddress(?AddressInterface $address): void
    {
        if (!$address || !$address->getCountryId()) {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The shipping address is missing. Set the address and try again."
            ];
        }
    }

    /**
     * Validate quote
     *
     * @param Quote $quote
     * @throws InputException
     * @return void
     */
    protected function validateQuote(Quote $quote): void
    {
        if (!$quote->getItemsCount()) {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The shipping method can\'t be set for an empty cart. Add an item to cart and try again."
            ];            
        }
    }

    /**
     * Prepare shipping assignment.
     *
     * @param CartInterface $quote
     * @param AddressInterface $address
     * @param string $addressFrom
     * @param string $method
     * @return CartInterface
     */
    private function prepareShippingAssignment(CartInterface $quote, AddressInterface $address, $method): CartInterface
    {
        $cartExtension = $quote->getExtensionAttributes();
        if ($cartExtension === null) {
            $cartExtension = $this->cartExtensionFactory->create();
        }

        $shippingAssignments = $cartExtension->getShippingAssignments();
        if (empty($shippingAssignments)) {
            $shippingAssignment = $this->shippingAssignmentFactory->create();
        } else {
            $shippingAssignment = $shippingAssignments[0];
        }
        
        $shipping = $shippingAssignment->getShipping();
        if ($shipping === null) {
            $shipping = $this->shippingFactory->create();
        }

        $shipping->setAddress($address);
        $shipping->setMethod($method);
        $shippingAssignment->setShipping($shipping);
        $cartExtension->setShippingAssignments([$shippingAssignment]);
        return $quote->setExtensionAttributes($cartExtension);
    }
}
 
?>
