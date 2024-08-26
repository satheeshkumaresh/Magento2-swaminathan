<?php
namespace Swaminathan\Checkout\Model;

use Swaminathan\Checkout\Api\CartManagementInterface;
use Razorpay\Magento\Model\PaymentMethod;

class PlaceRazorpayOrder implements \Swaminathan\Checkout\Api\PlaceRazorpayOrderInterface
{
    protected $scopeConfig;

    protected $cartManagement;

    protected $_objectManager;

    protected $order;

    protected $logger;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Swaminathan\Checkout\Api\CartManagementInterface $cartManagement,
        PaymentMethod $paymentMethod,
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->scopeConfig    = $scopeConfig;
        $this->cartManagement = $cartManagement;
        $this->rzp            = $paymentMethod->rzp;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->order          = $order;
        $this->logger         = $logger;
    }

    /**
     * @inheritdoc
     */
    public function placeRazorpayOrder($data)
    {   
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/rpz.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('RestAPI: Creating Razorpay Order');
        if(empty($data['order_id'])){
            $logger->info('RestAPI: Input Exception: Required parameter "order_id" is missing');
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "Parameter missing."
            ];
            return $response;
        }
        try
        {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $order_id   = $data['order_id'];
            $logger->info('RestAPI: Order ID: ' . $order_id);
            $order = $this->order->load($order_id, $this->order::INCREMENT_ID);
            $order_grand_total          = $order->getGrandTotal();
            $order_currency_code        = $order->getOrderCurrencyCode();
            $order_base_discount_amount = $order->getBaseDiscountAmount();
            if (null === $order_grand_total
                || null === $order_currency_code
                || null === $order_base_discount_amount)
            {
                $logger->info('RestAPI: Unable to fetch order data for Order ID: ' . $order_id);
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' => "Unable to fetch order data."
                ];
                return $response;
            }
            $amount          = (int) (number_format($order_grand_total * 100, 0, ".", ""));
            $payment_action  = $this->scopeConfig->getValue('payment/razorpay/rzp_payment_action', $storeScope);
            $payment_capture = 1;
            if ($payment_action === 'authorize')
            {
                $payment_capture = 0;
            }
            $logger->info('RestAPI: Data for Razorpay order , '
                . 'Amount:' . $amount . ', '
                . 'Receipt:' . $order_id . ', '
                . 'Currency:' . $order_currency_code . ', '
                . ' Payment Capture:' . $payment_capture);
            $razorpay_order = $this->rzp->order->create([
                'amount'          => $amount,
                'receipt'         => $order_id,
                'currency'        => $order_currency_code,
                'payment_capture' => $payment_capture,
                'app_offer'       => (($order_grand_total - $order_base_discount_amount) > 0) ? 1 : 0,
            ]);

            if (null !== $razorpay_order && !empty($razorpay_order->id))
            {
                if ($order)
                {
                    $logger->info('RestAPI: Razorpay Order ID: ' . $razorpay_order->id);
                    $order->setRzpOrderId($razorpay_order->id);
                }
                $order->save();

                $responseContent = [
                    'success'        => true,
                    'rzp_order_id'   => $razorpay_order->id,
                    'order_id'       => $order_id,
                    'amount'         => number_format((float) $order_grand_total, 2, ".", ""),
                    'currency'       => $order_currency_code,
                    'message'        => 'Razorpay Order created successfully.'
                ];
                $response[] = [
                    'code' => 200,
                    'status' => true, 
                    'message' => $responseContent
                ];
                return $response;
            } 
            else
            {
                $logger->info('RestAPI: Razorpay Order not generated. Something went wrong');
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' => "Razorpay Order not generated. Something went wrong"
                ];
                return $response;
            }
        } catch (\Razorpay\Api\Errors\Error $e)
        {
            $logger->info('RestAPI: Razorpay API Error: ' . $e->getMessage());
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => $e->getMessage()
            ];
            return $response;
        } catch (\Exception $e)
        {
            $logger->info('RestAPI: Exception: ' . $e->getMessage());
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => $e->getMessage()
            ];
            return $response;
        }
    }
    // Place Guest Razorpay Order 
    public function placeGuestRazorpayOrder($data){
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
                    return $this->placeRazorpayOrder($data);
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
            $logger->info('RestAPI: '
            . 'Error:' . $e->getMessage());
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => 'Error: %1.', $e->getMessage()
            ];
            return $response;
        }
    }
}

