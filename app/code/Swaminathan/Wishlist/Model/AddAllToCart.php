<?php
namespace Swaminathan\Wishlist\Model;

use Swaminathan\Wishlist\Api\AddAllToCartInterface;
use Swaminathan\Customer\Model\CustomerAddress;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Wishlist\Model\ItemCarrier;
use Magento\Wishlist\Model\ItemFactory;
use Swaminathan\Cart\Model\Quote\Item\Repository;
use Magento\Catalog\Api\ProductRepositoryInterface;
 
class AddAllToCart implements AddAllToCartInterface
{
    protected $customerAddress;
    protected $wishlistFactory;
    protected $itemCarrier;
    protected $itemFactory;
    protected $repository;
    protected $productRepository;
    public function __construct(
        CustomerAddress $customerAddress,
        WishlistFactory $wishlistFactory,
        ItemCarrier $itemCarrier,
        ItemFactory $itemFactory,
        Repository $repository,
        ProductRepositoryInterface $productRepository
    ) 
    {
        $this->customerAddress = $customerAddress;
        $this->wishlistFactory = $wishlistFactory;
        $this->itemCarrier = $itemCarrier;
        $this->itemFactory = $itemFactory;
        $this->repository = $repository;
        $this->productRepository = $productRepository;
    }

    /**
     * @return array
     */
    public function addAllToCart(){
        $cartItem = [];
        $qty = 1;
        $customerId = $this->customerAddress->getCustomerId(); 
        try{
            if(!empty($customerId)){
                $wishList = $this->wishlistFactory->create()->load($customerId, 'customer_id');
                $wishlistId = $wishList->getData()['wishlist_id'];
                $wishlistItems = $this->itemFactory->create()->getCollection();
                $wishlistItems->addFieldToFilter('wishlist_id',$wishlistId);
                if(!empty($wishlistItems->getData())){
                   $response =  $this->repository->addAllToCart($wishlistItems->getData(), $customerId);
                }
                else{
                    $response[] =[
                        "code" => 400,
                        "status" => false,
                        "message" => "You have no items in your wish list."
                    ];       
                }
            }
            else{
                $response[] =[
                    "code" => 400,
                    "status" => false,
                    "message" => "Invalid Authorication."
                ];         
            }
            return $response;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }
}