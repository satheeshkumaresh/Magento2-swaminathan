<?php
namespace Swaminathan\AccountInformation\Model;
use Magento\Sales\Model\OrderRepository;
use Magento\Catalog\Model\ProductFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use Swaminathan\Customer\Model\CustomerAddress;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\Setup\Module\Dependency\Parser\Composer\Json;
use Magento\TestFramework\Utility\ChildrenClassesSearch\E;

class ViewOrder 
{ 
    protected $orderRepository;
    
    public function __construct(
        OrderRepository $orderRepository,
        ProductFactory $productFactory,
        ProductHelper $productHelper,
        OrderCollection $orderCollection,
        UrlHelper $urlHelper,
        CustomerAddress $countryName,
        OrderInterfaceFactory $orderFactory,
        Order $tracksCollection,
      
        ) {
            $this->orderRepository = $orderRepository;
            $this->productFactory = $productFactory;
            $this->productHelper = $productHelper;
            $this->orderCollection = $orderCollection;
            $this->urlHelper = $urlHelper;
            $this->countryName=$countryName;
            $this->orderFactory = $orderFactory;
            $this->tracksCollection = $tracksCollection;
        }
    /**
    * @inheritdoc
    */
    
    public function viewOrder($orderId)
    {
        $orderDatas = $this->orderFactory->create()
            ->getCollection()
            ->addFieldToFilter('increment_id',  $orderId);
        $orderData = $orderDatas->getData();

        $orderDataId = $orderData[0]['entity_id'];

       
       
        
        if ($orderDatas->count()) { 
            $product = [];
            $trackerNumberData = "";
            $order = $this->orderRepository->get($orderData[0]['entity_id']);
            $orderDataId = $orderData[0]['entity_id'];
            $orderIdDetails = $this->orderFactory->create()->load($orderDataId);
            $trackerDetails = $orderIdDetails->getTracksCollection();
            
          $title=[];
            foreach ($trackerDetails->getData() as $value) {
           $title[] =    $value['title'];
           $trackerNumberData =  $value['track_number'];
            if($value['title'] == "DTDC"){
                $dtdc = "https://www.trackingmore.com/track/en/".$trackerNumberData."?express=dtdc";   
            }elseif($value['title'] == "professional"){
                $professional = "https://trackcourier.io/track-and-trace/professional-courier/".$trackerNumberData;    
            }
            }

            $order_info = [];
            $order_info['id'] = $order->getEntityId();
            $order_info['increment_id'] = $order->getIncrementId();
            $order_info['Date'] = $order->getCreatedAt();
            $order_info['state'] = $order->getState();
            $order_info['status'] = $order->getStatus();
            $order_info['store_id'] = $order->getStoreId();
            $order_info['grand_total'] = $this->productHelper->INDMoneyFormat($order->getGrandTotal());    
            $order_info['display_grand_total'] = $this->productHelper->getFormattedPrice($order->getGrandTotal());         
            $order_info['sub_total'] = $this->productHelper->INDMoneyFormat($order->getSubtotal());
            $order_info['display_sub_total'] = $this->productHelper->getFormattedPrice($order->getSubtotal());
            $order_info['total_qty'] = round($order->getTotalQtyOrdered());
            $order_info['currency_code'] = $order->getOrderCurrencyCode(); 
            $order_info['professional'] =  $professional ??  "" ;
            $order_info['DTDC'] = $dtdc ?? "";
                     

            $data['order_details'] = $order_info;
            
            // get Billing details 
            $billing_address = []; 
            $billingaddress = $order->getBillingAddress();
            $billing_address['firstname'] = $billingaddress->getFirstname();
            $billing_address['lastname'] = $billingaddress->getLastname();
            $billing_address['billingcity'] = $billingaddress->getCity();      
            $billing_address['billingstreet'] = $billingaddress->getStreet();
            $billing_address['billingpostcode'] = $billingaddress->getPostcode();
            $billing_address['billingtelephone'] = $billingaddress->getTelephone();
            $billing_address['country_name']=$this->countryName->getCountryname($billingaddress->getCountryId());
            $billing_address['region']=$billingaddress->getRegion();
            $data['billing_address'] = $billing_address;          
            // get shipping details
            $shipping_address = [];
            $shippingaddress = $order->getShippingAddress();   
            $shipping_address['firstname'] = $shippingaddress->getFirstname();   
            $shipping_address['lastname'] = $shippingaddress->getLastname();   
            $shipping_address['shippingcity'] = $shippingaddress->getCity();
            $shipping_address['shippingstreet'] =$shippingaddress->getStreet();
            $shipping_address['shippingpostcode'] = $shippingaddress->getPostcode();      
            $shipping_address['shippingtelephone'] = $shippingaddress->getTelephone();
            $shipping_address['country_name']=$this->countryName->getCountryname($shippingaddress->getCountryId());
            $shipping_address['region']=$shippingaddress->getRegion();
            $data['shipping_address'] = $shipping_address;
            //total amount
            $total_amount = [];
            $total_amount['discount_amount'] = $this->productHelper->INDMoneyFormat($order->getDiscountAmount());
            $total_amount['display_discount_amount'] = $this->productHelper->getFormattedPrice($order->getDiscountAmount());
            $total_amount['grandTotal'] = $this->productHelper->INDMoneyFormat($order->getGrandTotal());
            $total_amount['display_grandTotal'] = $this->productHelper->getFormattedPrice($order->getGrandTotal());
            $total_amount['subTotal'] = $this->productHelper->INDMoneyFormat($order->getSubtotal());
            $total_amount['display_subTotal'] = $this->productHelper->getFormattedPrice($order->getSubtotal());
            $total_amount['tax'] = $this->productHelper->INDMoneyFormat($order->getTaxAmount());
            $total_amount['display_tax'] = $this->productHelper->getFormattedPrice($order->getTaxAmount());
            $total_amount['shipping'] = $this->productHelper->INDMoneyFormat($order->getShippingAmount());
            $total_amount['display_shipping'] = $this->productHelper->getFormattedPrice($order->getShippingAmount());
            $data['total_amount'] = $total_amount;
            // fetch specific payment information
            $payment_information = [];
            $payment_information['amount'] = $order->getPayment()->getAmountPaid();
            $payment_information['payment_method'] = $order->getPayment()->getMethod();
            $payment_information['info'] = $order->getPayment()->getAdditionalInformation('method_title');
            $data['payment_information'] = $payment_information;
            $shippingMethod=[];
            $shippingMethod['shippingMethod']=$order->getShippingDescription();
            $data['method']= $shippingMethod;
            // Get Order Items
            $orderItems = $order->getAllItems();   
            foreach ($orderItems as $item) {
            $datas = [];
            $datas['ProductId'] = $item->getProductId();
            $datas['OrderId'] = $item->getOrderId();
            $datas['StoreId'] = $item->getStoreId();     
            $datas['Sku'] = $item->getSku();
            $datas['Product_name'] = $item->getName();
            $datas['qty'] = round($item->getQtyOrdered());
            $datas['price'] = $item->getPrice(); 
            $datas['display_price'] = $this->productHelper->INDMoneyFormat($datas['price']);
            $rowTotal = $datas['qty'] * $datas['price'];
            $datas['row_total'] =  $rowTotal;
            $datas['display_row_total']=$this->productHelper->INDMoneyFormat($rowTotal);
            $productId = $item->getProductId();
            $productData = $this->productFactory->create()->load($productId);
                $entityId = $productData->getEntityId();
                if($entityId != null){
            $datas['color'] = $this->productHelper->getColorAttributeValue($productData);
            $datas['weight_in_kg'] = $this->productHelper->getSizeAttributeValue($productData);
            $datas['product_url'] = $this->productHelper->getProductRewriteUrl($productId);
            $datas['image'] = $this->productHelper->getProductImage($productId);   
            } 
            else{
                $datas['color'] = ""; 
                $datas['weight_in_kg'] = "";
                $datas['product_url'] = "";
                $datas['image'] = $this->urlHelper->getPlaceHolderImage();
            }
            $product_data[] = $datas;
            }
            $data['product'] = $product_data;
            $product[]=$data;
            $response[] = [
                "code" => 200,
                "success" => true,
                "data" => $product
            ];
        }     
        else{
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Invalid orderId",
            ];
        }
       return $response;
    }
}
