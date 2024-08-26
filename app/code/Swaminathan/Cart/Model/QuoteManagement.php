<?php
namespace Swaminathan\Cart\Model;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Payment\Model\Method\AbstractMethod;
use Swaminathan\Checkout\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Address\ToOrder as ToOrderConverter;
use Magento\Quote\Model\Quote\Address\ToOrderAddress as ToOrderAddressConverter;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Item\ToOrderItem as ToOrderItemConverter;
use Magento\Quote\Model\Quote\Payment\ToOrderPayment as ToOrderPaymentConverter;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Sales\Api\OrderManagementInterface as OrderManagement;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteFactory;

class QuoteManagement extends \Swaminathan\Checkout\Model\QuoteManagement implements CartManagementInterface
{
    
    protected $eventManager;

    private $submitQuoteValidator;

    protected $orderFactory;

    protected $orderManagement;

    protected $customerManagement;

    protected $quoteAddressToOrder;

    protected $quoteAddressToOrderAddress;

    protected $quoteItemToOrderItem;

    protected $quotePaymentToOrderPayment;

    protected $userContext;

    protected $quoteRepository;

    protected $customerRepository;

    protected $customerModelFactory;

    protected $quoteAddressFactory;

    protected $dataObjectHelper;

    protected $storeManager;

    protected $checkoutSession;

    protected $customerSession;

    protected $accountManagement;

    protected $quoteFactory;

    private $quoteIdMaskFactory;

    private $addressRepository;

    private $addressesToSync = [];

    private $request;

    private $remoteAddress;

    public function __construct(
        EventManager $eventManager,
        \Magento\Quote\Model\SubmitQuoteValidator $submitQuoteValidator,
        OrderFactory $orderFactory,
        OrderManagement $orderManagement,
        \Magento\Quote\Model\CustomerManagement $customerManagement,
        ToOrderConverter $quoteAddressToOrder,
        ToOrderAddressConverter $quoteAddressToOrderAddress,
        ToOrderItemConverter $quoteItemToOrderItem,
        ToOrderPaymentConverter $quotePaymentToOrderPayment,
        UserContextInterface $userContext,
        CartRepositoryInterface $quoteRepository,
        CustomerRepositoryInterface $customerRepository,
        CustomerFactory $customerModelFactory,
        AddressFactory $quoteAddressFactory,
        DataObjectHelper $dataObjectHelper,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        AccountManagementInterface $accountManagement,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory = null,
        AddressRepositoryInterface $addressRepository = null,
        RequestInterface $request = null,
        RemoteAddress $remoteAddress = null
    ) {
        $this->eventManager = $eventManager;
        $this->submitQuoteValidator = $submitQuoteValidator;
        $this->orderFactory = $orderFactory;
        $this->orderManagement = $orderManagement;
        $this->customerManagement = $customerManagement;
        $this->quoteAddressToOrder = $quoteAddressToOrder;
        $this->quoteAddressToOrderAddress = $quoteAddressToOrderAddress;
        $this->quoteItemToOrderItem = $quoteItemToOrderItem;
        $this->quotePaymentToOrderPayment = $quotePaymentToOrderPayment;
        $this->userContext = $userContext;
        $this->quoteRepository = $quoteRepository;
        $this->customerRepository = $customerRepository;
        $this->customerModelFactory = $customerModelFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->accountManagement = $accountManagement;
        $this->customerSession = $customerSession;
        $this->quoteFactory = $quoteFactory;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory ?: ObjectManager::getInstance()
            ->get(\Magento\Quote\Model\QuoteIdMaskFactory::class);
        $this->addressRepository = $addressRepository ?: ObjectManager::getInstance()
            ->get(AddressRepositoryInterface::class);
        $this->request = $request ?: ObjectManager::getInstance()
            ->get(RequestInterface::class);
        $this->remoteAddress = $remoteAddress ?: ObjectManager::getInstance()
            ->get(RemoteAddress::class);
    }
    /**
     * @inheritdoc
     */
    public function createEmptyCartForCustomer($customerId)
    {
        $storeId = $this->storeManager->getStore()->getStoreId();
        $quote = $this->createCustomerCart($customerId, $storeId);
        try {
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__("The quote can't be created."));
        }
        return (int)$quote->getId();
    }
    public function createCustomerCart($customerId, $storeId)
    {
        return "";
    }
}