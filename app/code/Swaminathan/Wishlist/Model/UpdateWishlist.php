<?php
namespace Swaminathan\Wishlist\Model;
use Swaminathan\Wishlist\Api\UpdateWishlistInterface;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\ResourceModel\Item;


class UpdateWishlist implements UpdateWishlistInterface
{
  public function __construct(
    WishlistFactory $wishlist,
    ItemFactory $wishlistItemFactory
    
    
      ){
        $this->wishlist=$wishlist;
        $this->wishlistItemFactory=$wishlistItemFactory;
        
       }
    /**
     * Updatewishlist
     * @param string[] $data
     * @return array
     */
    public function updateWishlist($data) {

        $wishlistId = $data['wishlistId'];
        $wishlistItemId = $data['wishlistItemId'];
        $description=$data['description'];
        $qty=$data['qty'];
        $wishlist=$this->wishlist->create();
        $wishlist->load($wishlistId);
        $wishlistItemFactory=$this->wishlistItemFactory->create();   
        $wishlistItemFactory->load($wishlistItemId);
        $wishlistItemFactory->setDescription($description);
        if($wishlist->updateItem($wishlistItemFactory, new \Magento\Framework\DataObject(['qty' => $qty])))
        {
          
          $response[] =[
            "code" => 200,
            "status" => true,
            "message" => "Updated successful"
        ];
        }
        else{
          $response[] =[
            "code" => 400,
            "status" => false,
            "message" => "Update not successful"
        ];
        }
        return  $response;
        
      }

        
}
