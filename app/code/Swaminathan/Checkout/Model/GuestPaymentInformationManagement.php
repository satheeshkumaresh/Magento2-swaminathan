<?php
namespace Swaminathan\Checkout\Model;

use Magento\Checkout\Api\Exception\PaymentProcessingRateLimitExceededException;
use Magento\Checkout\Api\PaymentProcessingRateLimiterInterface;
use Magento\Checkout\Api\PaymentSavingRateLimiterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\OrderFactory;
use Swaminathan\Checkout\Helper\Data as TotalSummary;
use Magento\Quote\Model\QuoteRepository;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku; 
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;

class GuestPaymentInformationManagement implements \Swaminathan\Checkout\Api\GuestPaymentInformationManagementInterface
{
    protected $totalSummary;

    protected $billingAddressManagement;

    protected $orderFactory;

    protected $paymentMethodManagement;

    protected $cartManagement;

    protected $paymentInformationManagement;

    protected $quoteIdMaskFactory;

    protected $cartRepository;

    private $logger;

    private $paymentsRateLimiter;

    private $savingRateLimiter;

    private $saveRateLimitDisabled = false;

    protected $quoteRepository;

    protected $getSalableQuantityDataBySku;

    protected $stockRegistry;

    protected $storeManager;

    protected $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection,
        QuoteRepository $quoteRepository,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        StockRegistry $stockRegistry,
        StoreManagerInterface $storeManager,
        TotalSummary $totalSummary,
        \Magento\Quote\Api\GuestBillingAddressManagementInterface $billingAddressManagement,
        \Magento\Quote\Api\GuestPaymentMethodManagementInterface $paymentMethodManagement,
        \Swaminathan\Checkout\Api\GuestCartManagementInterface $cartManagement,
        \Magento\Checkout\Api\PaymentInformationManagementInterface $paymentInformationManagement,
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        CartRepositoryInterface $cartRepository,
        ?PaymentProcessingRateLimiterInterface $paymentsRateLimiter = null,
        ?PaymentSavingRateLimiterInterface $savingRateLimiter = null
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->stockRegistry = $stockRegistry;
        $this->quoteRepository = $quoteRepository;
        $this->totalSummary = $totalSummary;
        $this->billingAddressManagement = $billingAddressManagement;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartManagement = $cartManagement;
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->orderFactory = $orderFactory;
        $this->cartRepository = $cartRepository;
        $this->paymentsRateLimiter = $paymentsRateLimiter
            ?? ObjectManager::getInstance()->get(PaymentProcessingRateLimiterInterface::class);
        $this->savingRateLimiter = $savingRateLimiter
            ?? ObjectManager::getInstance()->get(PaymentSavingRateLimiterInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformationAndPlaceOrder(
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $flag = 0;
        $itemSku = [];
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($quoteIdMask->getQuoteId());
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
        $this->paymentsRateLimiter->limit();
        try {
            //Have to do this hack because of savePaymentInformation() plugins.
            $this->saveRateLimitDisabled = true;
            $this->savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);
        } finally {
            $this->saveRateLimitDisabled = false;
        }
        try {
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
            //$delete = $connection->delete($tableName, ['reservation_id = ?' => $reservationId]);
        }
        catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->getLogger()->critical(
                'Placing an order with quote_id ' . $cartId . ' is failed: ' . $e->getMessage()
            );
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "Error occured while placing order.", 
            ];
            return $response;
        } catch (\Exception $e) {
            $this->getLogger()->critical($e);
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "An error occurred on the server. Please try to place the order again.", 
            ];
            return $response;
        }
        $response[] = [
            'code' => 200,
            'status' => true, 
            'order_increment_id' => $orderIncrementId,
            'message' => "Order Created Successfully.", 
        ];
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function savePaymentInformation(
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $response = [];
        if (!$this->saveRateLimitDisabled) {
            try {
                $this->savingRateLimiter->limit();
            } catch (PaymentProcessingRateLimitExceededException $ex) {
                //Limit reached
                return false;
            }
        }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        /** @var Quote $quote */
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());

        if ($billingAddress) {
            $billingAddress->setEmail($email);
            $quote->removeAddress($quote->getBillingAddress()->getId());
            $quote->setBillingAddress($billingAddress);
            $quote->setDataChanges(true);
        } else {
            $quote->getBillingAddress()->setEmail($email);
        }
        $this->limitShippingCarrier($quote);

        if (!(int)$quote->getItemsQty()) {
            throw new CouldNotSaveException(__('Some of the products are disabled.'));
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
        $totalSummaryInfo = [];
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $paymentInfo = $this->paymentInformationManagement->getPaymentInformation($quoteIdMask->getQuoteId())->getData();
        $totalSummaryInfo = $this->totalSummary->getSummaryTotal($paymentInfo);
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
            $this->logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        }
        return $this->logger;
    }

    /**
     * Limits shipping rates request by carrier from shipping address.
     *
     * @param Quote $quote
     *
     * @return void
     * @see \Magento\Shipping\Model\Shipping::collectRates
     */
    private function limitShippingCarrier(Quote $quote) : void
    {
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress && $shippingAddress->getShippingMethod()) {
            $shippingRate = $shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod());
            if ($shippingRate) {
                $shippingAddress->setLimitCarrier($shippingRate->getCarrier());
            }
        }
    }
}
