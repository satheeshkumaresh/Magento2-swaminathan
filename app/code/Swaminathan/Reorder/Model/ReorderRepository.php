<?php
namespace Swaminathan\Reorder\Model;

use Swaminathan\Reorder\Api\ReorderRepositoryInterface;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart\CartInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;

use Magento\Checkout\Model\Session;

/**
 * Class OrderService
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReorderRepository extends DataObject implements ReorderRepositoryInterface
{
    protected $orderRepository;
    protected $cart;
    protected $_checkoutSession;
    protected $_productIds;
    protected $productRepository;

    protected $_resourceCart;
    protected $_customerSession;
    protected $messageManager;
    protected $stockRegistry;
    protected $stockState;
    protected $quoteRepository;
    private $requestInfoFilter;
    protected $orderFactory;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

        /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    protected $customerId;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\ResourceModel\Cart $resourceCart,
        Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepository,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ){
        $this->orderRepository = $orderRepository;
        $this->cart = $cart;
        $this->_eventManager = $eventManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_resourceCart = $resourceCart;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->stockRegistry = $stockRegistry;
        $this->stockState = $stockState;
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->orderFactory = $orderFactory;
        $this->customerRepository = $customerRepository;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * Add order item for the customer
     * @param int $customerId
     * @param int $orderId
     * @return array|bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function reorderItem($customerId, $orderId)
    {
        $this->customerId = $customerId;
        $order = $this->orderRepository->get($orderId);
        $cart = $this->cart;
        $items = $order->getItemsCollection();
       foreach ($items as $item) {
        //echo '<pre>';print_r($item->getData());
            try {
                $cart = $this->addOrderItem($item);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                if ($cart->getUseNotice(true)) {
                    return $e->getMessage();
                } else {
                    return $e->getMessage();
                }
            } catch (\Exception $e) {
                echo $e->getMessage();
                return "We can't add this item to your shopping cart right now";
            }
        }

        $cart = $this->save();
        return "Product successfully added to your cart";
    }

    /**
     * Convert order item to quote item
     *
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param true|null $qtyFlag if is null set product qty like in order
     * @return $this
     */
    public function addOrderItem($orderItem, $qtyFlag = null)
    {
        /* @var $orderItem \Magento\Sales\Model\Order\Item */
        if ($orderItem->getParentItem() === null) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                /**
                 * We need to reload product in this place, because products
                 * with the same id may have different sets of order attributes.
                 */
                $product = $this->productRepository->getById($orderItem->getProductId(), false, $storeId, true);
            } catch (NoSuchEntityException $e) {
                return $this;
            }
            $info = $orderItem->getProductOptionByCode('info_buyRequest');
            $info = new \Magento\Framework\DataObject($info);
            if ($qtyFlag === null) {
                $info->setQty($orderItem->getQtyOrdered());
            } else {
                $info->setQty(1);
            }

            $this->addProduct($product, $info);
        }
        return $this;
    }

    /**
     * Add product to shopping cart (quote)
     *
     * @param int|Product $productInfo
     * @param \Magento\Framework\DataObject|int|array $requestInfo
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addProduct($productInfo, $requestInfo = null)
    {
        $product = $this->_getProduct($productInfo);
        $request = $this->_getProductRequest($requestInfo);
        $productId = $product->getId();

        if ($productId) {
            $stockItem = $this->stockRegistry->getStockItem($productId, $product->getStore()->getWebsiteId());
            $minimumQty = $stockItem->getMinSaleQty();
            //If product quantity is not specified in request and there is set minimal qty for it
            if ($minimumQty
                && $minimumQty > 0
                && !$request->getQty()
            ) {
                $request->setQty($minimumQty);
            }

            try {
                $result = $this->getQuote()->addProduct($product, $request);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_checkoutSession->setUseNotice(false);
                $result = $e->getMessage();
            }
            /**
             * String we can get if prepare process has error
             */
            if (is_string($result)) {
                if ($product->hasOptionsValidationFail()) {
                    $redirectUrl = $product->getUrlModel()->getUrl(
                        $product,
                        ['_query' => ['startcustomization' => 1]]
                    );
                } else {
                    $redirectUrl = $product->getProductUrl();
                }
                $this->_checkoutSession->setRedirectUrl($redirectUrl);
                if ($this->_checkoutSession->getUseNotice() === null) {
                    $this->_checkoutSession->setUseNotice(true);
                }
                throw new \Magento\Framework\Exception\LocalizedException(__($result));
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('The product does not exist.'));
        }

        $this->_eventManager->dispatch(
            'checkout_cart_product_add_after',
            ['quote_item' => $result, 'product' => $product]
        );
        $this->_checkoutSession->setLastAddedProductId($productId);
        return $this;
    }

     /**
     * Get quote object associated with cart. By default it is current customer session quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        $storeId = $this->_storeManager->getStore()->getStoreId();
        $quote = $this->createCustomerCart($this->customerId, $storeId);

        if (!$this->hasData('quote')) {
            $this->setData('quote', $quote);
        }
        return $this->_getData('quote');
    }

     protected function createCustomerCart($customerId, $storeId)
    {
        try {
            $quote = $this->quoteRepository->getActiveForCustomer($customerId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $customer = $this->customerRepository->getById($customerId);
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->quoteFactory->create();
            $quote->setStoreId($storeId);
            $quote->setCustomer($customer);
            $quote->setCustomerIsGuest(0);
        }
        return $quote;
    }

    /**
     * Set quote object associated with the cart
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return $this
     * @codeCoverageIgnore
     */
    public function setQuote(\Magento\Quote\Model\Quote $quote)
    {
        $this->setData('quote', $quote);
        return $this;
    }

    /**
     * Get product object based on requested product information
     *
     * @param   Product|int|string $productInfo
     * @return  Product
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getProduct($productInfo)
    {
        $product = null;
        if ($productInfo instanceof Product) {
            $product = $productInfo;
            if (!$product->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("The product wasn't found. Verify the product and try again.")
                );
            }
        } elseif (is_int($productInfo) || is_string($productInfo)) {
            $storeId = $this->_storeManager->getStore()->getId();
            try {
                $product = $this->productRepository->getById($productInfo, false, $storeId);
            } catch (NoSuchEntityException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __("The product wasn't found. Verify the product and try again."),
                    $e
                );
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("The product wasn't found. Verify the product and try again.")
            );
        }
        $currentWebsiteId = $this->_storeManager->getStore()->getWebsiteId();
        if (!is_array($product->getWebsiteIds()) || !in_array($currentWebsiteId, $product->getWebsiteIds())) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("The product wasn't found. Verify the product and try again.")
            );
        }
        return $product;
    }

    /**
     * Get request for product add to cart procedure
     *
     * @param   \Magento\Framework\DataObject|int|array $requestInfo
     * @return  \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getProductRequest($requestInfo)
    {
        if ($requestInfo instanceof \Magento\Framework\DataObject) {
            $request = $requestInfo;
        } elseif (is_numeric($requestInfo)) {
            $request = new \Magento\Framework\DataObject(['qty' => $requestInfo]);
        } elseif (is_array($requestInfo)) {
            $request = new \Magento\Framework\DataObject($requestInfo);
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We found an invalid request for adding product to quote.')
            );
        }
        $this->getRequestInfoFilter()->filter($request);

        return $request;
    }

    /**
     * Getter for RequestInfoFilter
     *
     * @deprecated 100.1.2
     * @return \Magento\Checkout\Model\Cart\RequestInfoFilterInterface
     */
    private function getRequestInfoFilter()
    {
        if ($this->requestInfoFilter === null) {
            $this->requestInfoFilter = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Checkout\Model\Cart\RequestInfoFilterInterface::class);
        }
        return $this->requestInfoFilter;
    }

    /**
     * Save cart
     *
     * @return $this
     */
    public function save()
    {
        $this->_eventManager->dispatch('checkout_cart_save_before', ['cart' => $this]);

        $this->getQuote()->getBillingAddress();
        $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        $this->getQuote()->collectTotals();
        $this->quoteRepository->save($this->getQuote());
        $this->_checkoutSession->setQuoteId($this->getQuote()->getId());
        /**
         * Cart save usually called after changes with cart items.
         */
        $this->_eventManager->dispatch('checkout_cart_save_after', ['cart' => $this]);
        $this->reinitializeState();
        return $this;
    }

    /**
     * Reinitialize cart quote state
     *
     * @return $this
     */
    protected function reinitializeState()
    {
        $quote = $this->getQuote()->setCheckoutMethod('');
        $this->_checkoutSession->setCartWasUpdated(true);
        // TODO: Move this logic to Multishipping module as plug-in.
        // reset for multiple address checkout
        if ($this->_checkoutSession->getCheckoutState() !== Session::CHECKOUT_STATE_BEGIN
            && $this->_checkoutSession->getCheckoutState() !== null) {
            $quote->removeAllAddresses()->removePayment();
            $this->_checkoutSession->resetCheckout();
        }
        return $this;
    }

    /**
     * Save cart (implement interface method)
     *
     * @return void
     * @codeCoverageIgnore
     */
    public function saveQuote()
    {
        $this->save();
    }

    /**
     * Mark all quote items as deleted (empty shopping cart)
     *
     * @return $this
     * @codeCoverageIgnore
     */
    public function truncate()
    {
        $this->getQuote()->removeAllItems();
        return $this;
    }
}
