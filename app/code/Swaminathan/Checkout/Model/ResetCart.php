<?php
namespace Swaminathan\Checkout\Model;

use Razorpay\Magento\Model\PaymentMethod;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use Magento\Sales\Model\Order\AddressFactory as SalesOrderAddressFactory;
use Magento\Customer\Model\AddressFactory;

class ResetCart
{
	protected $quote;

	protected $checkoutSession;

    protected $logger;

    protected $quoteFactory;

    protected $orderFactory;

	protected $resultFactory;

    protected $urlHelper;

    protected $addressFactory;

    protected $salesOrderAddressFactory;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Razorpay\Magento\Model\CheckoutFactory $checkoutFactory,
        \Razorpay\Magento\Model\Config $config,
        \Magento\Catalog\Model\Session $catalogSession,
        \Psr\Log\LoggerInterface $logger,
        QuoteFactory $quoteFactory,
        OrderFactory $orderFactory,
        ResultFactory $resultFactory,
        UrlHelper $urlHelper,
        AddressFactory $addressFactory,
        SalesOrderAddressFactory $salesOrderAddressFactory
    ) {
        
        $this->checkoutSession = $checkoutSession;
        $this->checkoutFactory = $checkoutFactory;
        $this->catalogSession  = $catalogSession;
        $this->config          = $config;
        $this->logger          = $logger;
        $this->quoteFactory = $quoteFactory;
        $this->orderFactory = $orderFactory;
        $this->resultFactory = $resultFactory;
        $this->urlHelper = $urlHelper;
        $this->addressFactory = $addressFactory;
        $this->salesOrderAddressFactory = $salesOrderAddressFactory;
    }

    /**
     * Reset Cart 
     *
     * @param int $order_id
     * @return string
     */
    public function resetCart($order_id)
    {
        $responseContent=[];
        $reactUrl = $this->urlHelper->getReactUrl();
        $this->logger->info("Reset Cart started.");
        $lastOrderId = $order_id;
        $orderModel = $this->orderFactory->create()->load($lastOrderId, 'increment_id');
        $lastQuoteId = $orderModel->getData()['quote_id'];
        $state = $orderModel->getState();
        $status = $orderModel->getStatus();
        if ($lastQuoteId && $lastOrderId)
        {
            $this->logger->info("Reset Cart: with lastQuoteId:" . $lastQuoteId);
            if ($orderModel->canCancel())
            {
                $quote = $this->quoteFactory->create()->load($lastQuoteId);
                $quote->setIsActive(true)->save();
                $this->checkoutSession->replaceQuote($quote);
                //not canceling order as cancled order can't be used again for order processing.
                if($state != "canceled" && $status != "canceled"){
                $orderModel->setStatus('canceled');
                $orderModel->cancel();
                $orderModel->save();
                $this->logger->info("successfully reverted salable quantity");
                }
                else{
                    $this->logger->info("Already reverted salable quantity");
                }
                $this->checkoutSession->setFirstTimeChk('0');
                $this->logger->info("Reset Cart: redirect_url: checkout/#payment");
                $responseContent[] = [
                    'code' => 200,
                    'status' => true, 
                    'message' => "Cart retained succesfully.",
                    'redirect_url' => $reactUrl."checkout"
                ];
            }
        }
        if (!$lastQuoteId || !$lastOrderId)
        {
            $this->logger->info("Reset Cart: redirect_url: checkout/cart");
            $responseContent[] = [
                'code' => 200,
                'status' => true, 
                'message' => "The requested data is not found.",
                'redirect_url' => $reactUrl."mycart"
            ];
        }
        if($lastOrderId == "" || $lastQuoteId == ""){
            $this->logger->info("Reset Cart: redirect_url: checkout/cart");
            $responseContent[] = [
                'code' => 200,
                'status' => true, 
                'message' => "The requested order is not found.",
                'redirect_url' => $reactUrl."mycart"
            ];
        }
        $responseContent[] = [
            'code' => 200,
            'status' => true, 
            'message' => "Cart retained succesfully.",
            'redirect_url' => $reactUrl."checkout"
        ];
        $this->logger->critical("Reset Cart: Payment Failed or Payment closed");
        return $responseContent;
    }
}

