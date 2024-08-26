<?php
namespace Swaminathan\Checkout\Model;

use Magento\Checkout\Api\Exception\PaymentProcessingRateLimitExceededException;
use Magento\Checkout\Api\PaymentProcessingRateLimiterInterface;
use Magento\Checkout\Api\PaymentSavingRateLimiterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Swaminathan\Checkout\Helper\Data as TotalSummary;
use Magento\Quote\Model\QuoteRepository;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku; 
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;

class PaymentInformationManagement implements \Swaminathan\Checkout\Api\PaymentInformationManagementInterface
{
    protected $totalSummary;

    protected $billingAddressManagement;

    protected $paymentMethodManagement;
    
    protected $orderFactory;

    protected $cartManagement;

    protected $paymentDetailsFactory;

    protected $cartTotalsRepository;

    private $logger;

    private $cartRepository;

    private $paymentRateLimiter;

    private $saveRateLimiter;

    private $saveRateLimiterDisabled = false;

    protected $quoteRepository;

    protected $getSalableQuantityDataBySku;

    protected $stockRegistry;

    protected $resourceConnection;

    public function __construct(
        TotalSummary $totalSummary,
        \Magento\Quote\Api\BillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Swaminathan\Checkout\Api\CartManagementInterface $cartManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        QuoteRepository $quoteRepository,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        StockRegistry $stockRegistry,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection,
        ?PaymentProcessingRateLimiterInterface $paymentRateLimiter = null,
        ?PaymentSavingRateLimiterInterface $saveRateLimiter = null,
        ?CartRepositoryInterface $cartRepository = null
    ) {
        $this->storeManager = $storeManager;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->stockRegistry = $stockRegistry;
        $this->totalSummary = $totalSummary;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartManagement = $cartManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;
        $this->resourceConnection = $resourceConnection;
        $this->paymentRateLimiter = $paymentRateLimiter
            ?? ObjectManager::getInstance()->get(PaymentProcessingRateLimiterInterface::class);
        $this->saveRateLimiter = $saveRateLimiter
            ?? ObjectManager::getInstance()->get(PaymentSavingRateLimiterInterface::class);
        $this->cartRepository = $cartRepository
            ?? ObjectManager::getInstance()->get(CartRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $this->paymentRateLimiter->limit();
        try {
            //Have to do this hack because of plugins for savePaymentInformation()
            $this->saveRateLimiterDisabled = true;
            $this->savePaymentInformation($cartId, $paymentMethod, $billingAddress);
        } finally {
            $this->saveRateLimiterDisabled = false;
        }
        try {
            $flag = 0;
                $itemSku = [];
                // Get Cart Items by Quote Id
                $quote = $this->quoteRepository->get($cartId);
                $items = $quote->getAllItems();
                foreach($items as $item){
                    $itemSku[] = $item->getSku();
                }
                if(count($itemSku) > 0){
                    foreach($itemSku as $sku){
                        // Get salable quantity
                        $salable = $this->getSalableQuantityDataBySku->execute($sku);
                        $salableQty = $salable[0]['qty'];
                        // get Stock status
                        $stockStatus = $this->stockRegistry->getStockStatusBySku(
                            $sku,
                            $this->storeManager->getWebsite()->getId()
                        );
                        $stockData = $stockStatus->getStockItem();
                        $stockStatus = $stockData->getData()['is_in_stock'];
                        // Verify stock status
                        if($stockStatus == 0){
                            $flag++;
                        }
                        if($salableQty <= 0){
                            $flag++;
                        }
                    }
                }
                if($flag != 0){
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => "Some of the products are out of stock"
                    ];
                    return $response;
                }
            $orderId = $this->cartManagement->placeOrder($cartId);
            $orderIncrement = $this->orderFactory->create()->load($orderId);
            $orderIncrementId = $orderIncrement->getData()['increment_id'];
           // Below code salable quantity Affected so removed code
            // $connection = $this->resourceConnection->getConnection();
            // $tableName = $connection->getTableName('inventory_reservation');
            // $select = $connection->select()
            //     ->from($tableName)
            //     ->order('reservation_id DESC')
            //     ->limit(1);
            // $results = $connection->fetchAll($select);
            // $reservationId = $results[0]['reservation_id'];
            // $delete = $connection->delete($tableName, ['reservation_id = ?' => $reservationId]);
            // if($orderId && $delete){   
            // }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->getLogger()->critical(
                'Placing an order with quote_id ' . $cartId . ' is failed: ' . $e->getMessage()
            );
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "Error occured while placing order."
            ];
            return $response;
        } catch (\Exception $e) {
            $this->getLogger()->critical($e);
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "A server error stopped your order from being placed. Please try to place your order again."
            ];
            return $response;
        }
        $response[] = [
            'code' => 200,
            'status' => true, 
            'order_increment_id' => $orderIncrementId,
            'message' => "Order Created successfully.", 
        ];
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformation(
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $response = [];
        if (!$this->saveRateLimiterDisabled) {
            try {
                $this->saveRateLimiter->limit();
            } catch (PaymentProcessingRateLimitExceededException $ex) {
                //Limit reached
                return false;
            }
        }

        if ($billingAddress) {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepository->getActive($cartId);
            $customerId = $quote->getBillingAddress()
                ->getCustomerId();
            if (!$billingAddress->getCustomerId() && $customerId) {
                //It's necessary to verify the price rules with the customer data
                $billingAddress->setCustomerId($customerId);
            }
            $quote->removeAddress($quote->getBillingAddress()->getId());
            $quote->setBillingAddress($billingAddress);
            $quote->setDataChanges(true);
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress && $shippingAddress->getShippingMethod()) {
                $shippingRate = $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod());
                if ($shippingRate) {
                    $shippingAddress->setLimitCarrier($shippingRate->getCarrier());
                }
            }
        }
        $this->paymentMethodManagement->set($cartId, $paymentMethod);
        $response[] = [
            'code' => 200,
            'status' => true, 
            'message' => "Payment information set successfully.", 
        ];
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentInformation($cartId)
    {
        /** @var \Magento\Checkout\Api\Data\PaymentDetailsInterface $paymentDetails */
        $paymentDetails = $this->paymentDetailsFactory->create();
        $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
        $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));
        $totalSummaryInfo = $this->totalSummary->getSummaryTotal($paymentDetails);
        $response[] = [
            'code' => 200,
            'status' => true, 
            'data' => $totalSummaryInfo
        ];
        return $response;
    }

    /**
     * Get logger instance
     *
     * @return \Psr\Log\LoggerInterface
     * @deprecated 100.1.8
     */
    private function getLogger()
    {
        if (!$this->logger) {
            $this->logger = ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        }
        return $this->logger;
    }
}

