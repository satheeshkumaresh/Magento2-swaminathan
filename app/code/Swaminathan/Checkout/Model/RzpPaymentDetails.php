<?php
namespace Swaminathan\Checkout\Model;

use Razorpay\Magento\Model\PaymentMethod;
use Razorpay\Magento\Model\Config;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\Customer\Model\AddressFactory;
use Swaminathan\Customer\Model\CustomerAddress;
use Magento\Quote\Model\Quote\AddressFactory as QuoteAddressFactory;
use Magento\Sales\Model\Order\AddressFactory as SalesOrderAddressFactory;

/**
 * Mutation resolver for setting payment method for shopping cart
 */
class RzpPaymentDetails implements \Swaminathan\Checkout\Api\RzpPaymentDetailsInterface
{

    protected $salesOrderAddressFactory;

    protected $quoteAddressFactory;

    protected $customerAddress;

    protected $addressFactory;

    protected $resourceConnection;
    
    protected $order;

    protected $config;

    protected $invoiceService;

    protected $transaction;

    protected $checkoutSession;

    protected $invoiceSender;

    protected $orderSender;

    protected $scopeConfig;

    protected $enableCustomPaidOrderStatus;

    protected $orderStatus;

    protected $orderFactory;

    protected $quoteFactory;

    protected const STATUS_PROCESSING = 'processing';

    protected $quoteItemCollectionFactory;

    public function __construct(
        SalesOrderAddressFactory $salesOrderAddressFactory,
        QuoteAddressFactory $quoteAddressFactory,
        CustomerAddress $customerAddress,
        AddressFactory $addressFactory,
        ResourceConnection $resourceConnection,
        \Razorpay\Magento\Model\PaymentMethod $paymentMethod,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Razorpay\Magento\Model\Config $config,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        OrderFactory $orderFactory,
        QuoteFactory $quoteFactory,
        QuoteItemCollectionFactory $quoteItemCollectionFactory
    )
    {
        $this->salesOrderAddressFactory = $salesOrderAddressFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->customerAddress = $customerAddress;
        $this->addressFactory = $addressFactory;
        $this->resourceConnection = $resourceConnection;
        $this->rzp             = $paymentMethod->rzp;
        $this->order           = $order;
        $this->config          = $config;
        $this->invoiceService  = $invoiceService;
        $this->transaction     = $transaction;
        $this->scopeConfig     = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->invoiceSender   = $invoiceSender;
        $this->orderSender     = $orderSender;
        $this->orderStatus     = static::STATUS_PROCESSING;
        $this->orderFactory    = $orderFactory;
        $this->quoteFactory    = $quoteFactory;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;

        $this->enableCustomPaidOrderStatus = $this->config->isCustomPaidOrderStatusEnabled();

        if ($this->enableCustomPaidOrderStatus === true
            && empty($this->config->getCustomPaidOrderStatus()) === false)
        {
            $this->orderStatus = $this->config->getCustomPaidOrderStatus();
        }
    }

    /**
     * @inheritdoc
     */
    public function setRzpPaymentDetailsForOrder($shipping_address, $billing_address, $data)
    {
        // Get customer Id from bearer token
        $customerId = $this->customerAddress->getCustomerId();
        $lastShippingAddressId = "";
        $lastBillingAddressId = "";
        if (empty($data['order_id']) || empty($data['rzp_payment_id']) || (empty($data['rzp_signature'])))
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => 'Parameter missing.'
            ];
            return $response;
        }
        $order_id = $data['order_id'];
        $rzp_payment_id = $data['rzp_payment_id'];
        $rzp_signature = $data['rzp_signature'];


        $rzp_order_id = '';
        try
        {
            // Get order details by order increment id
            $order = $this->order->load($order_id, 'increment_id');
            if ($order)
            {
                // Get Razorpay order id
                $rzp_order_id = $order->getRzpOrderId();
                if(null !== $rzp_order_id)
                {
                    
                } else
                {
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => 'Something went wrong. Unable to process Razorpay Order.'
                    ];
                    return $response;
                }
            }
        } catch (\Exception $e)
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => $e->getMessage()
            ];
            return $response;
        }
       
        $attributes = [
            'razorpay_payment_id' => $rzp_payment_id,
            'razorpay_order_id'   => $rzp_order_id,
            'razorpay_signature'  => $rzp_signature
        ];
        // Verify Signature
        $this->rzp->utility->verifyPaymentSignature($attributes);
        try
        {
            // Get Payment Action
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $payment_action  = $this->scopeConfig->getValue('payment/razorpay/rzp_payment_action', $storeScope);
            $payment_capture = 'Captured';
            if ($payment_action === 'authorize')
            {
                $payment_capture = 'Authorized';
            }

            //fetch order from API
            $rzp_order_data = $this->rzp->order->fetch($rzp_order_id);
            $receipt = isset($rzp_order_data->receipt) ? $rzp_order_data->receipt : null;

            if ($receipt !== $order_id)
            {
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' => 'Razorpay order is invalid.'
                ];
                return $response;
            }
            $rzpOrderAmount = $rzp_order_data->amount;

            if ($order)
            {
                $amountPaid = number_format($rzpOrderAmount / 100, 2, ".", "");
                // Update order status to processing
                if ($order->getStatus() === 'pending')
                {
                    $order->setState(static::STATUS_PROCESSING)->setStatus($this->orderStatus);
                }

                $payment = $order->getPayment();

                $payment->setLastTransId($rzp_payment_id)
                        ->setTransactionId($rzp_payment_id)
                        ->setIsTransactionClosed(true)
                        ->setShouldCloseParentTransaction(true);

                $payment->setParentTransactionId($payment->getTransactionId());

                if ($this->config->getPaymentAction()  === \Razorpay\Magento\Model\PaymentMethod::ACTION_AUTHORIZE_CAPTURE)
                {
                    $payment->addTransactionCommentsToOrder(
                        "$rzp_payment_id",
                        (new CaptureCommand())->execute(
                            $payment,
                            $order->getGrandTotal(),
                            $order
                        ),
                        ""
                    );
                } else
                {
                    $payment->addTransactionCommentsToOrder(
                        "$rzp_payment_id",
                        (new AuthorizeCommand())->execute(
                            $payment,
                            $order->getGrandTotal(),
                            $order
                        ),
                        ""
                    );
                }

                $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, "");

                $transaction->setIsClosed(true);

                $transaction->save();

                if ($order->canInvoice() && $this->config->canAutoGenerateInvoice()
                    && $rzp_order_data->status === 'paid')
                {
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                    $invoice->setTransactionId($rzp_payment_id);
                    $invoice->register();
                    $invoice->save();
                    $transactionSave = $this->transaction
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transactionSave->save();

                    $this->invoiceSender->send($invoice);

                    $order->addStatusHistoryComment(
                        __('Notified customer about invoice #%1.', $invoice->getId())
                    )->setIsCustomerNotified(true);
                }

                try
                {
                    $this->checkoutSession->setRazorpayMailSentOnSuccess(true);
                    $this->orderSender->send($order);
                    $this->checkoutSession->unsRazorpayMailSentOnSuccess();
                }
                catch (\Magento\Framework\Exception\MailException $e)
                {
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => 'Razorpay Error: %1.', $e->getMessage()
                    ];
                    return $response;
                }
                catch (\Exception $e)
                {
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => 'Error: %1.', $e->getMessage()
                    ];
                    return $response;
                }
                $order->save();
                 // Save in address book funcionality
                 $customerAddressData = $this->addressFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('parent_id', $customerId);
                $customerAddressCount = count($customerAddressData->getData());
                if($shipping_address['saveInAddressBook'] == 1){
                    $shippingAddress = $this->addressFactory->create();
                    $shippingAddress->setData('parent_id', $customerId);
                    $shippingAddress->setData('is_active', 1);
                    $shippingAddress->setData('city', $shipping_address['city']);
                    $shippingAddress->setData('company', $shipping_address['company']);
                    $shippingAddress->setData('country_id', $shipping_address['countryId']);
                    $shippingAddress->setData('firstname', $shipping_address['firstname']);
                    $shippingAddress->setData('lastname', $shipping_address['lastname']);
                    $shippingAddress->setData('postcode', $shipping_address['postcode']);
                    $shippingAddress->setData('region', $shipping_address['region']);
                    $shippingAddress->setData('region_id', $shipping_address['regionId']);
                    $shippingAddress->setData('street', $shipping_address['street']);
                    $shippingAddress->setData('telephone', $shipping_address['telephone']);
                    if($customerAddressCount == 0){
                        $shippingAddress->setData('is_default_shipping', 1);
                        if($billing_address['saveInAddressBook'] == 0){
                            $shippingAddress->setData('is_default_billing', 1);
                        }
                    }
                    $shippingAddress->save();
                    $lastShippingAddressId = $this->addressFactory->create()
                        ->getCollection()->getLastItem()->getId();
                }
                if($billing_address['saveInAddressBook'] == 1){
                    $billingAddress = $this->addressFactory->create();
                    $billingAddress->setData('parent_id', $customerId);
                    $billingAddress->setData('is_active', 1);
                    $billingAddress->setData('city', $billing_address['city']);
                    $billingAddress->setData('company', $billing_address['company']);
                    $billingAddress->setData('country_id', $billing_address['countryId']);
                    $billingAddress->setData('firstname', $billing_address['firstname']);
                    $billingAddress->setData('lastname', $billing_address['lastname']);
                    $billingAddress->setData('postcode', $billing_address['postcode']);
                    $billingAddress->setData('region', $billing_address['region']);
                    $billingAddress->setData('region_id', $billing_address['regionId']);
                    $billingAddress->setData('street', $billing_address['street']);
                    $billingAddress->setData('telephone', $billing_address['telephone']);
                    if($customerAddressCount == 0){
                        $billingAddress->setData('is_default_billing', 1);
                    }
                    $billingAddress->save();
                    $lastBillingAddressId = $this->addressFactory->create()
                        ->getCollection()->getLastItem()->getId();
                }
                 // Save the address
                $order = $this->orderFactory->create()->getCollection()
                    ->addFieldToFilter('increment_id', $data['order_id']);
                if ($order->getData()[0]['entity_id']) {
                    $quoteId = $order->getData()[0]['quote_id'];
                    // Get Quote addres by quote id in shipping
                    $shippingQuoteAddress = $this->quoteAddressFactory->create()->getCollection()
                        ->addFieldToFilter('quote_id', $quoteId)
                        ->addFieldToFilter('address_type',"shipping");
                    $shippingAddressId = $shippingQuoteAddress->getData()[0]['address_id'];
                    // load address id by quote address  in shipping
                    $shippingQuoteAddress = $this->quoteAddressFactory->create()
                        ->load($shippingAddressId, 'address_id');
                    $shippingQuoteAddress->setCustomerAddressId($lastShippingAddressId);
                    $shippingQuoteAddress->save();
                    // Get Quote addres by quote id in billing
                    $billingQuoteAddress = $this->quoteAddressFactory->create()->getCollection()
                        ->addFieldToFilter('quote_id', $quoteId)
                        ->addFieldToFilter('address_type',"billing");
                    $billingAddressId = $billingQuoteAddress->getData()[0]['address_id'];
                    // load address id by quote address  in billing
                    $billingQuoteAddress = $this->quoteAddressFactory->create()
                        ->load($billingAddressId, 'address_id');
                    $billingQuoteAddress->setCustomerAddressId($lastBillingAddressId);
                    $billingQuoteAddress->save();
                    // get billing address id
                    $salesBillingAddressId = $order->getData()[0]['billing_address_id'];
                    // set billing address id in sales address
                    $salesBillingAddress = $this->salesOrderAddressFactory->create()->load($salesBillingAddressId);
                    $salesBillingAddress->setCustomerAddressId($lastBillingAddressId);
                    $salesBillingAddress->save();
                    // get shipping address id
                    $salesShippingAddressId = $order->getData()[0]['shipping_address_id'];
                    // set billing address id in sales address
                    $salesShippingAddress = $this->salesOrderAddressFactory->create()->load($salesShippingAddressId);
                    $salesShippingAddress->setCustomerAddressId($lastShippingAddressId);
                    $salesShippingAddress->save();
                    $quote = $this->quoteFactory->create()->load($quoteId);
                    $quote->setIsActive(false);
                    $quote->save();
                    // Reduce the product's salable quantity
                    $quoteItemCollection = $this->quoteItemCollectionFactory->create();
                    $quoteItemCollection->addFieldToFilter('quote_id', $quoteId);
                    //Below code salable quantity Affected so removed code
                    // if(count($quoteItemCollection->getData()) > 0){
                    //     foreach($quoteItemCollection->getData() as $quoteItem){
                    //         $productId = $quoteItem['product_id'];
                    //         $qtyToReduce = -$quoteItem['qty'];
                    //         $productSku = $quoteItem['sku'];
                    //         $stockId = $quoteItem['store_id'];
                    //         $metaData['event_type'] = 'order_payment_success';
                    //         $metaData['object_type'] = 'order';
                    //         $metaData['object_id'] = '';
                    //         $metaData['object_increment_id'] = $order->getData()[0]['increment_id'];
                    //         $connection = $this->resourceConnection->getConnection();
                    //         $tableName = $this->resourceConnection->getTableName('inventory_reservation');
                    
                    //         $columns = [
                    //             ReservationInterface::STOCK_ID,
                    //             ReservationInterface::SKU,
                    //             ReservationInterface::QUANTITY,
                    //             ReservationInterface::METADATA,
                    //         ];
                    
                    //         $data = [];
                    //         $data[] = [
                    //             $stockId,
                    //             $productSku,
                    //             $qtyToReduce,
                    //             json_encode($metaData)
                    //         ];
                    //         $connection->insertArray($tableName, $columns, $data);
                    //     }
                    // }
                }
            }
        } catch (\Razorpay\Api\Errors\Error $e)
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => 'Razorpay Error: %1.', $e->getMessage()
            ];
            return $response;

        } catch (\Exception $e)
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => 'Error: %1.', $e->getMessage()
            ];
            return $response;
        }
        $response[] = [
            'code' => 200,
            'status' => true, 
            'order_id' => $receipt,
            'message' => 'Order created successfully.'
        ];
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function guestRzpPaymentDetailsForOrder($data)
    {
        if (empty($data['order_id']) || empty($data['rzp_payment_id']) || (empty($data['rzp_signature'])))
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => 'Parameter missing.'
            ];
            return $response;
        }
        $order_id = $data['order_id'];
        $rzp_payment_id = $data['rzp_payment_id'];
        $rzp_signature = $data['rzp_signature'];


        $rzp_order_id = '';
        try
        {
            // Get order details by order increment id
            $order = $this->order->load($order_id, 'increment_id');
            if ($order)
            {
                // Get Razorpay order id
                $rzp_order_id = $order->getRzpOrderId();
                if(null !== $rzp_order_id)
                {
                   
                } else
                {
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => 'Something went wrong. Unable to process Razorpay Order.'
                    ];
                    return $response;
                }
            }
        } catch (\Exception $e)
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => $e->getMessage()
            ];
            return $response;
        }
        $attributes = [
            'razorpay_payment_id' => $rzp_payment_id,
            'razorpay_order_id'   => $rzp_order_id,
            'razorpay_signature'  => $rzp_signature
        ];
        // Verify Signature
        $this->rzp->utility->verifyPaymentSignature($attributes);
        try
        {
            // Get Payment Action
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $payment_action  = $this->scopeConfig->getValue('payment/razorpay/rzp_payment_action', $storeScope);
            $payment_capture = 'Captured';
            if ($payment_action === 'authorize')
            {
                $payment_capture = 'Authorized';
            }

            //fetch order from API
            $rzp_order_data = $this->rzp->order->fetch($rzp_order_id);
            $receipt = isset($rzp_order_data->receipt) ? $rzp_order_data->receipt : null;

            if ($receipt !== $order_id)
            {
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' => 'Razorpay order is invalid.'
                ];
                return $response;
            }
            $rzpOrderAmount = $rzp_order_data->amount;

            if ($order)
            {
                $amountPaid = number_format($rzpOrderAmount / 100, 2, ".", "");
                // Update order status to processing
                if ($order->getStatus() === 'pending')
                {
                    $order->setState(static::STATUS_PROCESSING)->setStatus($this->orderStatus);
                }

                $payment = $order->getPayment();

                $payment->setLastTransId($rzp_payment_id)
                        ->setTransactionId($rzp_payment_id)
                        ->setIsTransactionClosed(true)
                        ->setShouldCloseParentTransaction(true);

                $payment->setParentTransactionId($payment->getTransactionId());

                if ($this->config->getPaymentAction()  === \Razorpay\Magento\Model\PaymentMethod::ACTION_AUTHORIZE_CAPTURE)
                {
                    $payment->addTransactionCommentsToOrder(
                        "$rzp_payment_id",
                        (new CaptureCommand())->execute(
                            $payment,
                            $order->getGrandTotal(),
                            $order
                        ),
                        ""
                    );
                } else
                {
                    $payment->addTransactionCommentsToOrder(
                        "$rzp_payment_id",
                        (new AuthorizeCommand())->execute(
                            $payment,
                            $order->getGrandTotal(),
                            $order
                        ),
                        ""
                    );
                }

                $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH, null, true, "");

                $transaction->setIsClosed(true);

                $transaction->save();

                if ($order->canInvoice() && $this->config->canAutoGenerateInvoice()
                    && $rzp_order_data->status === 'paid')
                {
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                    $invoice->setTransactionId($rzp_payment_id);
                    $invoice->register();
                    $invoice->save();
                    $transactionSave = $this->transaction
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                    $transactionSave->save();

                    $this->invoiceSender->send($invoice);

                    $order->addStatusHistoryComment(
                        __('Notified customer about invoice #%1.', $invoice->getId())
                    )->setIsCustomerNotified(true);
                }

                try
                {
                    $this->checkoutSession->setRazorpayMailSentOnSuccess(true);
                    $this->orderSender->send($order);
                    $this->checkoutSession->unsRazorpayMailSentOnSuccess();
                }
                catch (\Magento\Framework\Exception\MailException $e)
                {
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => 'Razorpay Error: %1.', $e->getMessage()
                    ];
                    return $response;
                }
                catch (\Exception $e)
                {
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => 'Error: %1.', $e->getMessage()
                    ];
                    return $response;
                }
            $order->save();
            $datas = [];
            // Save the address
            $order = $this->orderFactory->create()->getCollection()
                ->addFieldToFilter('increment_id', $data['order_id']);
             if ($order->getData()[0]['entity_id']) {
                 $quoteId = $order->getData()[0]['quote_id'];
                 $quote = $this->quoteFactory->create()->load($quoteId);
                 $quote->setIsActive(false);
                 $quote->save();
                 // Reduce the product's salable quantity
                 $quoteItemCollection = $this->quoteItemCollectionFactory->create();
                 $quoteItemCollection->addFieldToFilter('quote_id', $quoteId);
                 //Below code salable quantity Affected so removed code
                //  if(count($quoteItemCollection->getData()) > 0){
                //      foreach($quoteItemCollection->getData() as $quoteItem){
                //          $productId = $quoteItem['product_id'];
                //          $qtyToReduce = -$quoteItem['qty'];
                //          $productSku = $quoteItem['sku'];
                //          $stockId = $quoteItem['store_id'];
                //          $metaData['event_type'] = 'order_payment_success';
                //          $metaData['object_type'] = 'order';
                //          $metaData['object_id'] = '';
                //          $metaData['object_increment_id'] = $order->getData()[0]['increment_id'];
                //          $connection = $this->resourceConnection->getConnection();
                //          $tableName = $this->resourceConnection->getTableName('inventory_reservation');
                //          $datas = [
                //             $stockId,
                //             $productSku,
                //             $qtyToReduce,
                //             json_encode($metaData), // Assuming metadata is stored as JSON
                //         ];
                //          $columns = [
                //              ReservationInterface::STOCK_ID,
                //              ReservationInterface::SKU,
                //              ReservationInterface::QUANTITY,
                //              ReservationInterface::METADATA,
                //          ];
                //          $connection->insertArray($tableName, $columns, [$datas]);
                //         }
                //     }
                }
            }
        } catch (\Razorpay\Api\Errors\Error $e)
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => 'Razorpay Error: %1.', $e->getMessage()
            ];
            return $response;

        } catch (\Exception $e)
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => 'Error: %1.', $e->getMessage()
            ];
            return $response;
        }
        $response[] = [
            'code' => 200,
            'status' => true, 
            'order_id' => $receipt,
            'message' => 'Order created successfully.'
        ];
        return $response;
    }
    // Set guest Rzp Payment Details For Order
    public function setGuestRzpPaymentDetailsForOrder($data){
        try{
            $order = $this->order->load($data['order_id'], 'increment_id');
            $orderedCustomerEmail = $order->getCustomerEmail();
            if(isset($data['email']) && $data['email'] != ""){
                if($orderedCustomerEmail != $data['email']){
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => "The requested data invalid."
                    ];
                    return $response;
                }
                else{
                    return $this->guestRzpPaymentDetailsForOrder($data);
                }
            }
            else{
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' => "Parameter missing."
                ];
                return $response;
            }
        }
        catch (\Exception $e)
        {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => 'Error: %1.', $e->getMessage()
            ];
            return $response;
        }
    }
}
