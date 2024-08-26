<?php
namespace Swaminathan\Reorder\Model;
use Magento\Quote\Model\Quote\ItemFactory;
use Swaminathan\Reorder\Api\ReorderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\App\Request\Http;
use Magento\Integration\Model\Oauth\TokenFactory;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Swaminathan\CmsPlpPdp\Model\CmsPlpPdp;
use Magento\Sales\Model\Order;
class Reorder implements ReorderInterface
{ 
    protected $orderRepository;
    public function __construct(
        OrderRepository $orderRepository,
        ProductRepositoryInterface $productRep,
        CartRepositoryInterface $cartRep,
        QuoteFactory $quoteFactory,
        ProductHelper $ProductHelper,
        Http $http,
        TokenFactory $tokenFactory,
        OrderCollection $orderCollection,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        ItemFactory $quoteItemFactory,
        CmsPlpPdp $cmsPlpPdp,
        Order $orderData
      
        ) {
            $this->orderCollection = $orderCollection;
            $this->orderRepository = $orderRepository;
            $this->productRep = $productRep;
            $this->cartRep = $cartRep;
            $this->quoteFactory = $quoteFactory;
            $this->productHelper = $ProductHelper;
            $this->http = $http;
            $this->tokenFactory = $tokenFactory;
            $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
            $this->quoteItemFactory = $quoteItemFactory;
            $this->cmsPlpPdp = $cmsPlpPdp;
            $this->orderData=$orderData;
        }
    /**
     *@param int $orderId
    * @return array
    * @inheritdoc
    */
    public function reorderItem($orderId)
    {
        $response=[];
        $requestedQty=[];
        $noOfMinQty=[];
        $errorAddCart=[];
        $unavailable=[];
        $message = [];
        $authorizationHeader = $this->http->getHeader("Authorization");
        $tokenParts = explode("Bearer", $authorizationHeader);
        $tokenPayload = trim(array_pop($tokenParts));
        /** @var Token $token */
        $token = $this->tokenFactory->create();
        $token->loadByToken($tokenPayload);
        $customerId = $token->getCustomerId();
        $orderInfo = $this->orderData->loadByIncrementId($orderId);
        $entityId = $orderInfo->getEntityId();
        $collection = $this->orderCollection->addFieldToFilter('entity_Id', $entityId)->addFieldToFilter('customer_id', $customerId);
        $orderIdCount = count($collection->getData());
        $message['orderid_validation'] = "";
        if(!$entityId ||$orderIdCount == 0){
            $message['orderid_validation'] = "Invaild order id";
         }else{
            $i = 0;
            $j = 0;
            $order=$this->orderRepository->get($entityId);
            $quote = $this->cartRep->getActiveForCustomer($customerId);
            $cartId=$quote->getId();
            $cart = $this->quoteFactory->create()->loadActive($quote->getId());
            foreach($order->getAllItems() as $item){
            $productid=$item->getProductId();
            $orderQty=($item->getQtyOrdered());
            $sku=$item->getSku();
            $minQty = $this->cmsPlpPdp->getMinSaleQtyById($productid);
            $productRepo = $this->productRep->getById($productid);
            $productName = $productRepo->getProductName();
            $quoteItems = $this->quoteItemFactory->create()->getCollection()->addFieldToFilter('quote_id',$cartId)->addFieldToFilter('product_id', $productid);
             foreach($quoteItems as $items){
                $quoteQty=$items->getQty();
              }
            $StockState = $this->getSalableQuantityDataBySku;
            $qty_sal = $StockState->execute($sku);
            if(!empty($orderQty)){
                if(!empty($quoteQty)){
                    $totalQty=$orderQty+$quoteQty;
                }else{
                    $totalQty=$orderQty;
                }  
                $productScalableQty = $qty_sal[0]['qty'];
                if($totalQty <= $productScalableQty){
                    if($orderQty>= $minQty){
                    $product = $this->productRep->getById($productid);
                    $cart->addProduct($product, $orderQty);
                    $cart->collectTotals();
                    $cart->save();
                    $addedCart[] = $productName;
                    $i++; 
                    }
                    else{
                        $requestedQty[] = $productName; 
                       $noOfMinQty[]= $minQty;
                    }            
                }else{
                   $errorAddCart[] = $productName;
                   $j++;
                }
             }
             else{
                $unavailable[] = $productName;
                $j++;
             }
            }
            $message['unavailable'] = "";
            $message['added_cart'] = "";
            $message['stock_error'] = "";
            $message['qty_unavailable']="";
            if(!empty($unavailable)){
                $message['unavailable'] = 'There are no source items with the in stock status for "'.implode(",",$unavailable).'".';
            }
            if(!empty($addedCart)){
                $message['added_cart'] = $i. ' product(s) have been added to shopping cart: "'.implode(",",$addedCart).'".';
            }
            if(!empty($errorAddCart)){
                $message['stock_error'] = 'There are no source items with the in stock status for "'.implode(",",$errorAddCart).'".';
            }
            if(!empty($requestedQty)){
                $message['qty_unavailable'] = ''.implode(",",$requestedQty).' has been fewest you may purchase is '.implode($noOfMinQty).'.';
            }
         }
         $response[] = [
            'code' => 200,
            'status' => true, 
            'message' => $message
        ];  
      return $response;
    }
}