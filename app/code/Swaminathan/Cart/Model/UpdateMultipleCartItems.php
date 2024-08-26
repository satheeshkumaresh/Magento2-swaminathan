<?php
namespace Swaminathan\Cart\Model;

use Magento\Framework\DataObjectFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

class UpdateMultipleCartItems implements \Swaminathan\Cart\Api\UpdateMultipleItems
{
    protected $dataObjectFactory;
    protected $quoteRepository;
    protected $quoteItemFactory;
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        CartRepositoryInterface $quoteRepository,
        ItemFactory $quoteItemFactory,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku
    ) 
    {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
    }
    /**
     * @param string $cartId
     * @param mixed $cartItem
     * @return array
     */
    public function updateItems($cartId, $cartItems)
    {
        $quoteItems = $this->quoteItemFactory->create()->getCollection();
        $quoteItems->addFieldToFilter('quote_id',$cartId);
        if(!empty($quoteItems->getData())){
            $result = $this->dataObjectFactory->create();
            $quote = $this->quoteRepository->getActive($cartId);
            $i = 0;
            $j = 0;
            $productScalableQty = 0;
            foreach ($cartItems as $itemValues) {
                $item = $this->quoteItemFactory->create()->load($itemValues['item_id']);
                $gropedItem = $this->quoteItemFactory->create()->getCollection();
                $gropedItem->addFieldToFilter('item_id',$itemValues['item_id']);
                $quoteId= $gropedItem->getData()[0]['quote_id'];
                $productId=$gropedItem->getData()[0]['product_id'];
                $groupedProductCount= $this->quoteItemFactory->create()->getCollection()->addFieldToFilter('quote_id',$quoteId)->addFieldToFilter('product_id', $productId)->getData();
                $productScalableQty = 0;
                $StockState = $this->getSalableQuantityDataBySku;
                $qty = $StockState->execute($item['sku']);
                if(!empty($qty)){
                    $productScalableQty = $qty[0]['qty'];
                }
                if(count($groupedProductCount) == 2){
                    $groupedProductQty = $groupedProductCount[0]['qty'];
                    $simpleProductQty =$groupedProductCount[1]['qty'];
                    $groupedProduct = $groupedProductCount[0]['item_id'] == $itemValues['item_id'];
                    $simpleProduct = $groupedProductCount[1]['item_id'] == $itemValues['item_id'];
                    if(($groupedProduct == true &&($itemValues['qty']+$simpleProductQty <= $productScalableQty))|| ($simpleProduct == true && ($itemValues['qty']+$groupedProductQty <= $productScalableQty)))
                       {
                        $item = $this->quoteItemFactory->create()->load($itemValues['item_id']);
                        $productScalableQty = $qty[0]['qty'];
                        $item = $quote->getItemById($itemValues['item_id']);
                        $item->setQty($itemValues['qty']);
                        $item->save();
                        $i++;
                       }
                       else{
                        $j++;
                       }
                }else{
                    $addedQty = abs($productScalableQty) - abs($itemValues['qty']);
                    if(abs($itemValues['qty']) <= abs($productScalableQty)){
                        $item = $this->quoteItemFactory->create()->load($itemValues['item_id']);
                        $productScalableQty = $qty[0]['qty'];
                        $item = $quote->getItemById($itemValues['item_id']);
                        $item->setQty($itemValues['qty']);
                        $item->save();
                        $i++;
                    }
                    else{
                        $j++;
                    }
               }
            }
            if($i != 0 && $j == 0){ 
                $response[] = [
                    'code' => 200,
                    'status' => true, 
                    'message' =>"Cart Items Quantity Updated Successfully."
                ];
            } 
            else{
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' =>"The requested quantity doesn't exist."
                ];
            }  
        }
        else{
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' =>"The requested cart details doesn't exist."
            ];
        }
        return $response;
    }
}
