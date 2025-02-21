<?php
namespace Swaminathan\Wishlist\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Wishlist\Model\ResourceModel\Item as ItemResource;
use Swaminathan\Wishlist\Api\WishlistInterface;
use Swaminathan\Wishlist\Api\WishlistRepositoryInterface;

/**
 * Class WishlistRepository
 * @package Swaminathan\Wishlist\Model
 */
class WishlistRepository implements WishlistRepositoryInterface
{

    /**
     * @var Http
     */
    private $http;

    /**
     * @var TokenFactory
     */
    private $tokenFactory;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ItemResource
     */
    private $itemResource;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * WishlistRepository constructor.
     * @param Http $http
     * @param TokenFactory $tokenFactory
     * @param WishlistFactory $wishlistFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ItemResource $itemResource
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Http $http,
        TokenFactory $tokenFactory,
        WishlistFactory $wishlistFactory,
        ProductRepositoryInterface $productRepository,
        ItemResource $itemResource,
        CustomerSession $customerSession
    ) {
        $this->http = $http;
        $this->tokenFactory = $tokenFactory;
        $this->wishlistFactory = $wishlistFactory;
        $this->productRepository = $productRepository;
        $this->itemResource = $itemResource;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): WishlistInterface
    {
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            $authorizationHeader = $this->http->getHeader('Authorization');

            $tokenParts = explode('Bearer', $authorizationHeader);
            $tokenPayload = trim(array_pop($tokenParts));

            /** @var Token $token */
            $token = $this->tokenFactory->create();
            $token->loadByToken($tokenPayload);

            $customerId = $token->getCustomerId();
        }
        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId($customerId);

        if (!$wishlist->getId()) {
            $wishlist->setCustomerId($customerId);
            $wishlist->getResource()->save($wishlist);
        }

        return $wishlist;
    }

    /**
     * @inheritdoc
     */
    public function addItem(string $sku): bool
    {
        $product = $this->productRepository->get($sku);
        $wishlist = $this->getCurrent();
        $wishlist->addNewItem($product);
        return true;
    }
    /**
     * @inheritdoc
     */
    public function removeItem(int $itemId)
    {
        $wishlist = $this->getCurrent();
        $item = $wishlist->getItem($itemId);
        $productName = $item->getName();
        if (!$item) {
            $response[] =[
                "code" => 400,
                "status" => false,
                "message" => "Wishlist Product Item Not Found"
            ];
            return $response;
        }
       $itemremove= $this->itemResource->delete($item);
       if( $itemremove)
       {
        $response[] = ["code" => 200,'status' => true, 'message' =>"$productName has been removed from your Wish List."];
       }
       else{
        $response[] =[
            "code" => 400,
            "status" => false,
            "message" => "Wishlist Product Remove Failed"
        ];
       }
        return  $response;
    }
}
