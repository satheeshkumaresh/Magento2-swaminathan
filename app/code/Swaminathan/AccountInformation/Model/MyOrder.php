<?php
namespace Swaminathan\AccountInformation\Model;
use Swaminathan\AccountInformation\Api\MyOrderInterface;
use Magento\Framework\App\Request\Http;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\OrderFactory;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Magento\Sales\Model\OrderRepository;
use Magento\Catalog\Model\Product;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Sales\Model\Order\PaymentFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderModelFactory;

class MyOrder implements MyOrderInterface
{
    const PAGE_LIMIT = 15;

    const PENDING = "pending";

    const CANCELED= "canceled";

    const RECENT_ORDER_PAGE_LIMIT = 5;

    protected $paymentFactory;

    protected $orderModelFactory;

    public function __construct(
        ProductHelper $productHelper,
        Http $http,
        TokenFactory $tokenFactory,
        OrderCollection $orderCollection,
        OrderFactory $OrderFactory,
        Product $product,
        OrderRepository $orderRepository,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        PaymentFactory $paymentFactory,
        OrderModelFactory $orderModelFactory 
    ) {
        $this->http = $http;
        $this->tokenFactory = $tokenFactory;
        $this->orderCollection=$orderCollection;
        $this->orderFactory = $OrderFactory;
        $this->productHelper = $productHelper;
        $this->_product = $product;
        $this->orderRepository = $orderRepository;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->paymentFactory = $paymentFactory;
        $this->orderModelFactory = $orderModelFactory;
    }

    public function getCustomerId()
    {
        $authorizationHeader = $this->http->getHeader("Authorization");
        $tokenParts = explode("Bearer", $authorizationHeader);
        $tokenPayload = trim(array_pop($tokenParts));
        /** @var Token $token */
        $token = $this->tokenFactory->create();
        $token->loadByToken($tokenPayload);
        $customerId = $token->getCustomerId();
        return $customerId;
    }
    /**
     * 
     *@inheritdoc
     * @param  mixed $pageSize
     * @param  int $currPage
     * @return array 
     */
    public function getMyOrderDetail($pageSize,$currPage)
    {
        $customerId=$this->getCustomerId();
        if(!$customerId)
        {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message"=> "Invalid  customer Id"
            ];
        }
        else{
            $data = [];
            $orderData=[];
            // get Total Count
            $orderTotalCollection=$this->orderCollection;
            $orderTotalCollection->addFieldToFilter( 'status', ['neq' => "canceled"]);
            $orderTotalCollection->addFieldToFilter( 'state', ['neq' => "canceled"]);
            $orderTotalCollection->addFieldToFilter( 'customer_id', ['eq' => $customerId]);
            $totalcount = (count($orderTotalCollection->getdata()));

            $countCollection = $this->orderModelFactory->create();
            $countCollection->getSelect()
                ->joinLeft(
                    ['payment' => $countCollection->getTable('sales_order_payment')],
                    'main_table.entity_id = payment.parent_id',
                    []
                )
                ->where('main_table.customer_id = ?', $customerId)
                ->order('payment.entity_id DESC');
                //$totalcount = (count($countCollection->getdata()));
            $orderCollection = $this->orderModelFactory->create();
            $orderCollection->getSelect()
                ->joinLeft(
                    ['payment' => $orderCollection->getTable('sales_order_payment')],
                    'main_table.entity_id = payment.parent_id',
                    []
                )
                ->where('main_table.customer_id = ?', $customerId)
                ->order('payment.entity_id DESC');
            if($pageSize == 0 || $pageSize == ""){   
                $orderCollection->addFieldToFilter( 'state', ['neq' => "canceled"]);
                $orderCollection->addFieldToFilter( 'status', ['neq' => "canceled"]); 
                $orderCollection->setPageSize(self::PAGE_LIMIT);
                $orderCollection->setCurPage($currPage);       
             }
             else{
                $orderCollection->addFieldToFilter( 'state', ['neq' => "canceled"]);
                $orderCollection->addFieldToFilter( 'status', ['neq' => "canceled"]); 
                $orderCollection->setPageSize($pageSize);
                $orderCollection->setCurPage($currPage);       
             }
            foreach($orderCollection as $order)
            {
                $orderId=$order->getIncrementId();
                $data['order_id'] = $order->getIncrementId();
                $data['date'] = $order->getCreatedAt();
                $data['ship_to'] = $order->getCustomerFirstname();
                $orderTotal = $order->getBaseGrandTotal();
                $data['order_total'] = $this->productHelper->INDMoneyFormat($orderTotal);
                $data['display_order_total'] = $this->productHelper->getFormattedPrice($orderTotal);
                $data['status'] = $order->getStatus();
                $order=$this->orderRepository->get($order->getId());
                $i=0;
                $j=0;
                foreach($order->getAllItems() as $item){  
                    $productid=$item->getProductId(); 
                    $orderQty=($item->getQtyOrdered());
                    $sku = $item->getSku();  
                        $StockState = $this->getSalableQuantityDataBySku;
                            if($this->_product->getIdBySku($sku)) {  
                                $data['isExists'] = true;
                                $qty_sal = $StockState->execute($sku);
                                $isExists = $qty_sal[0]['qty'];
                                if($isExists > 0)  {
                                    $i++;
                                
                                }else{
                                $j++;
                                }   
                            }else{
                                $data['isExists']=false;
                            }      
                        }
                        if($i != 0 && $j == 0){
                            $data['stock_status']=true;
                        }
                        else{
                            $data['stock_status']=false;
                        }
                $orderData[] = $data;
            }
            $response[] = [
                "code" => 200,
                "success" => true,
                "count" => $totalcount,
                "show_per" =>$pageSize,
                "page" => $currPage,
                "orderdata" => $orderData                       
            ];
      }
      
      return $response;
    }
     /**
     *@inheritdoc
     * @return array
     */
    public function getRecentOrderDetail(){
        $customerId=$this->getCustomerId();
        if(!$customerId)
        {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message"=> "Invalid  customer Id"
            ];
        }
        else{
            $orderData=[];
            $orderCollection = $this->orderModelFactory->create();
            $orderCollection->getSelect()
                ->joinLeft(
                    ['payment' => $orderCollection->getTable('sales_order_payment')],
                    'main_table.entity_id = payment.parent_id',
                    []
                )
                ->where('main_table.customer_id = ?', $customerId)
                ->order('payment.entity_id DESC');
            $orderCollection->setOrder(
                'created_at',
                'desc'
            );
            $orderCollection->addFieldToFilter( 'state', ['neq' => "canceled"]);
            $orderCollection->addFieldToFilter( 'status', ['neq' => "canceled"]); 
            $orderCollection->setPageSize(self::RECENT_ORDER_PAGE_LIMIT);
            $limit = 1;    
            foreach($orderCollection as $order)
            {
                $paymentInfo = $this->paymentFactory->create()->load($order->getId(), 'parent_id');
                $lastTransactionId = $paymentInfo->getData()['last_trans_id'];
                if($limit <= 5){
                $data['order_id'] = $order->getIncrementId();
                $data['date'] = $order->getCreatedAt();
                $data['ship_to'] = $order->getCustomerFirstname();
                $data['order_total'] = $this->productHelper->INDMoneyFormat($order->getBaseGrandTotal());
                $data['display_order_total'] = $this->productHelper->getFormattedPrice($order->getBaseGrandTotal());
                $data['status'] = $order->getStatus();
                $order=$this->orderRepository->get($order->getId());
                $i=0;
                $j=0;
                foreach($order->getAllItems() as $item){  
                    $productid=$item->getProductId(); 
                    $orderQty=($item->getQtyOrdered());
                    $sku = $item->getSku();  
                           $StockState = $this->getSalableQuantityDataBySku;
                            if($this->_product->getIdBySku($sku)) {  
                                $data['isExists'] = true;
                                 $qty_sal = $StockState->execute($sku);
                                 $isExists = $qty_sal[0]['qty'];
                                 if($isExists > 0)  {
                                    $i++;
                                  
                                 }else{
                                   $j++;
                                 }   
                            }else{
                                $data['isExists']=false;
                            }      
                        }
                        if($i != 0 && $j == 0){
                            $data['stock_status']=true;
                        }
                        else{
                            $data['stock_status']=false;
                }
                $orderData[] = $data;
                $limit++;
              }
            }
            $response[] = [
                "code" => 200,
                "success" => true,
                "orderdata" => $orderData,
            ];
        }
        return $response;

    }
   
}