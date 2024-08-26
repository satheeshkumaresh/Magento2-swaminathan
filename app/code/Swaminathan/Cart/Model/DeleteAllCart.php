<?php
namespace Swaminathan\Cart\Model;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory ;
class DeleteAllCart implements \Swaminathan\Cart\Api\DeleteAllCart
{
    protected $quoteRepository;
    protected $quoteItemCollectionFactory;
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteItemCollectionFactory $quoteItemCollectionFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
    }

    public function deleteAllCart($cartId)
    {
        $quoteItem = $this->quoteItemCollectionFactory->create();
        $quoteItem->addFieldToFilter('quote_id', $cartId);
        if(!empty($quoteItem->getData())){
            $quote = $this->quoteRepository->getActive($cartId);
            /** @var  \Magento\Quote\Model\Quote\Item  $item */
            foreach ($quote->getAllVisibleItems() as $item) {
                if(!empty($item->getData())){
                    $delete = $item->delete();
                }
            }
            $response[] =[
                "code" => 200,
                "status" => true,
                "message" => "Cart Items Cleared Successfully."
            ]; 
        }
        else{
            $response[] =[
                "code" => 400,
                "status" => false,
                "message" => "The requested cart details doesn't exist."
            ]; 
        }
        return $response;
    }
}
