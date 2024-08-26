<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Swaminathan\Cart\Model\Quote\Item;

use Swaminathan\Cart\Api\UpdateMultipleItems;
use Swaminathan\Cart\Api\CartItemRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Quote\Model\Quote\Item\CartItemOptionsProcessor;
use Magento\Catalog\Model\ProductFactory;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Swaminathan\Cart\Helper\Data as DataHelper;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Swaminathan\Wishlist\Model\WishlistRepository;
use Magento\Framework\App\Request\Http;
use Magento\Integration\Model\Oauth\TokenFactory;
use Swaminathan\Checkout\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory as WishlistCollectionFactory;
use Magento\Quote\Model\Quote\ItemFactory as QuoteItemFactory;
use Swaminathan\CmsPlpPdp\Model\CmsPlpPdp;
use Magento\Catalog\Model\ProductRepository;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;

/**
 * Repository for quote item.
 */
class Repository implements CartItemRepositoryInterface
{
    /**
     * Url Helper
     *
     * @var UrlHelper
     */
    protected $urlHelper;
    /**
     * Product Helper
     *
     * @var DataHelper
     */
    protected $dataHelper;

        /**
     * Product Helper
     *
     * @var ProductHelper
     */
    protected $productHelper;

     /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * Quote Item Collection Factory
     *
     * @var QuoteItemCollectionFactory
     */
    protected $quoteItemCollectionFactory;

    /**
     * Get Salable Quantity Data By Sku
     *
     * @var GetSalableQuantityDataBySku
     */
    protected $getSalableQuantityDataBySku;

    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

     /**
     * Product Factory.
     *
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * Product repository.
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CartItemInterfaceFactory
     */
    protected $itemDataFactory;

    /**
     * @var CartItemProcessorInterface[]
     */
    protected $cartItemProcessors;

    /**
     * @var CartItemOptionsProcessor
     */
    private $cartItemOptionsProcessor;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;
    
    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var CmsPlpPdp
     */
    private $cmsPlpPdp;

    protected $_productRepository;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CartItemInterfaceFactory $itemDataFactory
     * @param CartItemOptionsProcessor $cartItemOptionsProcessor
     * @param CartItemProcessorInterface[] $cartItemProcessors
     * @param ProductFactory $productFactory
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param DataHelper $dataHelper
     * @param QuoteItemCollectionFactory $quoteItemCollectionFactory
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param ProductHelper $productHelper
     * @param WishlistRepository $wishlistRepository
     * @param CartManagementInterface $cartManagement
     * @param QuoteFactory $quoteFactory
     * @param ItemFactory $itemFactory
     * @param CmsPlpPdp $cmsPlpPdp
     * @param ProductRepository $_productRepository
     * @param UrlHelper $urlHelper
     */
    public function __construct(
        Http $http,
        TokenFactory $tokenFactory,
        CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepository,
        CartItemInterfaceFactory $itemDataFactory,
        CartItemOptionsProcessor $cartItemOptionsProcessor,
        ProductFactory $productFactory,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        DataHelper $dataHelper,
        QuoteItemCollectionFactory $quoteItemCollectionFactory,
        CartTotalRepositoryInterface $cartTotalRepository,
        ProductHelper $productHelper,
        WishlistRepository $wishlistRepository,
        CartManagementInterface $cartManagement,
        QuoteFactory $quoteFactory,
        ItemFactory $itemFactory,
        WishlistCollectionFactory $wishlistCollectionFactory,
        QuoteItemFactory $quoteItemFactory,
        CmsPlpPdp $cmsPlpPdp,
        ProductRepository $_productRepository,
        UrlHelper $urlHelper,
        array $cartItemProcessors = []
        
    ) {
        $this->http = $http;
        $this->tokenFactory = $tokenFactory;
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->itemDataFactory = $itemDataFactory;
        $this->cartItemOptionsProcessor = $cartItemOptionsProcessor;
        $this->cartItemProcessors = $cartItemProcessors;
        $this->productFactory = $productFactory;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->dataHelper = $dataHelper;
        $this->quoteItemCollectionFactory = $quoteItemCollectionFactory;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->productHelper = $productHelper;
        $this->wishlistRepository=$wishlistRepository;
        $this->cartManagement = $cartManagement;
        $this->quoteFactory = $quoteFactory;
        $this->itemFactory = $itemFactory;
        $this->wishlistCollectionFactory=$wishlistCollectionFactory;
        $this->quoteItemFactory=$quoteItemFactory;
        $this->cmsPlpPdp = $cmsPlpPdp;
        $this->_productRepository = $_productRepository;
        $this->urlHelper = $urlHelper;
    }
    

    /**
     * @inheritdoc
     */
    public function getList($cartId)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->collectTotals();
        $data=[];
        $crossSellProducts = [];
        /** @var  \Magento\Quote\Model\Quote\Item  $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            $productId = $item->getData()['product_id'];
            $data[]=$this->dataHelper->getQuoteItem($item);
            if(!empty($this->dataHelper->getCrossSellProducts($productId))){
                $crossSellProducts[] = $this->dataHelper->getCrossSellProducts($productId);
            }
        }
        $crossSell = [];
        foreach ($crossSellProducts as $key => $value) {
            $crossSell = array_merge($crossSell,$value); 
        }
        $productLimit = $this->urlHelper->getCrossSellProductLimit();
        $crossSellCollections = [];
        $i = 1;
        foreach($crossSell as $crossSellProductCollection){
            if($i <= $productLimit){
                $crossSellCollections[] = $crossSellProductCollection;
            }
            $i++;
        }
        $quoteItems = $this->cartTotalRepository->get($cartId)->getData();
        $cartSubTotal = $this->productHelper->getFormattedPrice($quoteItems['subtotal']);
        $displayCartSubTotal = $this->productHelper->INDMoneyFormat($quoteItems['subtotal']);
        $itemsQty = $this->productHelper->getFormattedPrice($quoteItems['items_qty']);
        $count = 0;
        foreach($data as $guestCartItems){
            $count = $count + $guestCartItems['qty'];
        }
        if(!empty($data)){
            $response[] = [
                "code" => 200,
                "status" => true,
                "total_no_of_items" => $count,
                "sub_total" => $cartSubTotal,
                "display_sub_total" => $displayCartSubTotal,
                "items_qty" => $itemsQty,
                "data"=>$data,
                "cross_sell_products" => $crossSellCollections
            ];
        }
        else{
            $response[] =[
                "code" => 200,
                "status" => true,
                "message" => "You have no items in your shopping cart."
            ];
        }
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function save(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $cartId = $cartItem->getQuoteId();
        if (!$cartId) {
            $response[] = [
                'code' => 400,
                'status' => false, 
                'message' => "The requested quote doesn't exist"
            ];
            return $response;
        }

        $quote = $this->quoteRepository->getActive($cartId);
        $quoteItems = $quote->getItems();
        $quoteItems[] = $cartItem;
        $quote->setItems($quoteItems);
        $this->quoteRepository->save($quote);
        $quote->collectTotals();
        return $quote->getLastAddedItem();
    }
    public function addAllToCart($wishlistItems, $customerId)
    {
        $addedCart = [];
        $unavailable = [];
        $errorAddCart = [];
        $groupedProduct = [];
        $i = 0;
        foreach($wishlistItems as $wishlistItem){
            $productId = $wishlistItem['product_id'];
            $wishlistItemId = $wishlistItem['wishlist_item_id'];
            $productRepo = $this->productRepository->getById($productId);
            $typeId = $productRepo->getTypeId();
            $sku = $productRepo->getSku();
            $productName = $productRepo->getProductName();
            $requestQuantity = round($wishlistItem['qty']);
            $quoteId = $this->cartManagement->createEmptyCartForCustomer($customerId);
            if (!$quoteId) {
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' => "The requested quote doesn't exist"
                ];
                return $response;
            }
            $cart = $this->quoteFactory->create()->loadActive($quoteId);
            if($typeId == "simple"){
                $productSalableQuantity = 0;
                $quoteItems = $this->quoteItemFactory->create()->getCollection()->addFieldToFilter('quote_id',$quoteId)->addFieldToFilter('product_id', $productId);
             foreach($quoteItems as $items){
                $quoteQty=$items->getQty();
              }
              if(!empty($quoteQty)){
                $totalQty=$requestQuantity+$quoteQty;
                }else{
                    $totalQty=$requestQuantity;
                }  
                $stockState = $this->getSalableQuantityDataBySku;
                $productSalableqty = $stockState->execute($sku);
                $minQty = $this->cmsPlpPdp->getMinSaleQtyById($productId);
             if(!empty($productSalableqty)){
                $productSalableQuantity = $productSalableqty[0]['qty'];
                  if(!empty($productSalableQuantity)){
                    $productSalableQuantity = $productSalableqty[0]['qty'];
                    if($totalQty <= $productSalableQuantity){
                        if(($requestQuantity >= $minQty)){
                        $cart->addProduct($productRepo, $requestQuantity);
                        $cart->collectTotals();
                        $save = $cart->save();
                        if($save){
                            $wishlistItems = $this->itemFactory->create()->load($wishlistItemId, 'wishlist_item_id')->delete();
                            $addedCart[] = $productName;
                            $i++;
                        }
                        else{
                            $errorAddCart[] = $productName;
                        }
                      }else{
                        $requestedQty[] = $productName;  
                    }
                  }     
                 else{
                        $salableQty[] = $productName;
                    }                 
                }
                else{
                    $unavailable[] = $productName;
                     
                }
             }
             else{
                $unavailable[] = $productName;
             }
            }
            else if($typeId = "grouped"){
                $groupedProduct[] = $productName;  
            }
        }
        $message = [];
        $message['unavailable'] = "";
        $message['added_cart'] = "";
        $message['grouped_product'] = "";
        $message['qty_unavailable'] = "";
        $message['salableQty'] = "";
        if(!empty($unavailable)){
            $message['unavailable'] = 'We could not add the following product(s) to the shopping cart "'.implode(",",$unavailable).'".';
        }
        if(!empty($addedCart)){
            $message['added_cart'] = $i. ' product(s) have been added to shopping cart: "'.implode(",",$addedCart).'".';
        }
        if(!empty($groupedProduct)){
            $message['grouped_product'] = 'Please specify the quantity of product(s) for "'.implode(",", $groupedProduct).'".';
        }
        if(!empty($requestedQty)){
            $message['qty_unavailable'] = ''.implode(",",$requestedQty).' has been fewest you may purchase is '.$minQty.'.';
        }
        if(!empty($salableQty)){
            $message['salableQty'] = 'The requested product quantity is not available. "'.implode(",",$salableQty).'".';
        }
        $response[] = [
            'code' => 200,
            'status' => true, 
            'message' => $message
        ];
        return $response;
    }
    /**
     * 
     * @inheritdoc
     */
    public function addToCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {
        $cartItems = $cartItem->getData();
        if(isset($cartItems['sku']) && isset($cartItems['qty'])){
            $productRepo = $this->_productRepository->get($cartItems['sku']);
            $productData = $this->productFactory->create()->load($productRepo->getId());
            $product = $productData->getData();
            if(!empty($product)){
                $productId = $product['entity_id'];
                $productType = $product['type_id'];
                $minQty = $this->cmsPlpPdp->getMinSaleQtyById($productId);
                if($cartItems['qty'] < $minQty && $cartItems['qty'] != 0){
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => 'The fewest you may purchase is '.$minQty.'.'
                    ];
                    return $response;
                }
                if($productType == "simple"){
                    $productScalableQty = 0;
                    $StockState = $this->getSalableQuantityDataBySku;
                    $qty = $StockState->execute($cartItems['sku']);
                    if(!empty($qty)){
                        $productScalableQty = $qty[0]['qty'];
                        /** @var \Magento\Quote\Model\Quote $quote */
                        $cartId = $cartItem->getQuoteId();
                        if (!$cartId) {
                            $response[] = [
                                'code' => 400,
                                'status' => false, 
                                'message' => "The requested quote doesn't exist"
                            ];
                            return $response;
                        }
                        
                        $quote = $this->quoteRepository->getActive($cartId);
                        $quoteQty = 0;  
                        foreach ($quote->getAllVisibleItems() as $item) {
                            if($cartItems['sku'] ==  $item->getSku()){
                                $quoteQty = $item->getQty();
                            }
                        }
                        if(isset($cartItems['item_id'])){
                            // If the product is already in cart means update cart items 
                            if($cartItems['qty'] <= $productScalableQty){
                                if(isset($cartItems['item_id'])){
                                    $quoteItemsFactory = $this->quoteItemCollectionFactory->create();
                                    $quoteItemsFactory->addFieldToFilter('item_id',$cartItems['item_id']);
                                }
                                $quoteItems = $quote->getItems();
                                $ItemId=$cartItem->getItemId();
                                $quoteItems[] = $cartItem;
                                $quote->setItems($quoteItems);
                                if(isset($cartItems['item_id']) && (!empty($quoteItemsFactory->getData()))){
                                    $groupedProductQuoteId=$quoteItemsFactory->getData()[0]['quote_id'];
                                    $groupedProductId=$quoteItemsFactory->getData()[0]['product_id'];
                                    $quoteFactory = $this->quoteItemCollectionFactory->create();
                                    $quoteFactory->addFieldToFilter('quote_id',$groupedProductQuoteId)->addFieldToFilter('product_id', $groupedProductId);
                                    $groupedProductCount=$quoteFactory->getData();
                                    if(count($groupedProductCount) == 2)
                                    {
                                        $groupedProductQty = $groupedProductCount[0]['qty'];
                                        $simpleProductQty =$groupedProductCount[1]['qty'];
                                        $groupedProduct = $groupedProductCount[0]['item_id'] == $cartItems['item_id'];
                                        $simpleProduct = $groupedProductCount[1]['item_id'] == $cartItems['item_id'];
                                        if(($groupedProduct== true &&($cartItems['qty']+$simpleProductQty<=$productScalableQty))|| ($simpleProduct==true && ($cartItems['qty']+$groupedProductQty<=$productScalableQty)))
                                        {
                                            $quoteSave = $this->quoteRepository->save($quote);
                                            $quote->collectTotals();
                                            $productname =$quote->getLastAddedItem()->getName();
                                            /** @var  \Magento\Quote\Model\Quote\Item  $item */
                                            foreach ($quote->getAllVisibleItems() as $item) {
                                                $data[]=$this->dataHelper->getQuoteItem($item);
                                            }
                                            $response[] = [
                                                'code' => 200,
                                                'status' => true, 
                                                'message' => "Quantity Updated Successfully.",
                                                'data' => $data
                                            ]; 
                                        }else{
                                            $response[] = [
                                                'code' => 400,
                                                'status' => false, 
                                                'message' => "The requested product quantity is not available."
                                            ];

                                        }
                                    }else{
                                    $quoteSave = $this->quoteRepository->save($quote);
                                    $quote->collectTotals();
                                    $productname =$quote->getLastAddedItem()->getName();
                                    /** @var  \Magento\Quote\Model\Quote\Item  $item */
                                    foreach ($quote->getAllVisibleItems() as $item) {
                                        $data[]=$this->dataHelper->getQuoteItem($item);
                                    }
                                    $response[] = [
                                        'code' => 200,
                                        'status' => true, 
                                        'message' => "Quantity Updated Successfully.",
                                        'data' => $data
                                    ]; 
                                }  
                                }
                                else if(!isset($cartItems['item_id'])) {
                                    $quoteSave = $this->quoteRepository->save($quote);
                                    $productname =$quote->getLastAddedItem()->getName();
                                    $response[] = [
                                        'code' => 200,
                                        'status' => true, 
                                        'message' =>"You added $productname to your shopping cart."
                                    ];
                                }
                                else{
                                    $response[] = [
                                        'code' => 400,
                                        'status' => false, 
                                        'message' =>"The Quote Item is not found.Verify the item and try again."
                                    ];
                                }
                            }
                            else{
                                $response[] =[
                                    "code" => 400,
                                    "status" => false,
                                    "message" => "The requested product quantity is not available."
                                ];
                            }
                        }  
                        else{
                            $addedQty = $productScalableQty - $quoteQty;
                            if($cartItems['qty'] <= $addedQty || $quoteQty == ""){
                                if(isset($cartItems['item_id'])){
                                    $quoteItemsFactory = $this->quoteItemCollectionFactory->create();
                                    $quoteItemsFactory->addFieldToFilter('item_id',$cartItems['item_id']);
                                }
                                $quoteItems = $quote->getItems();
                                $ItemId=$cartItem->getItemId();
                                $quoteItems[] = $cartItem;
                                $quote->setItems($quoteItems);
                                if(isset($cartItems['item_id']) && (!empty($quoteItemsFactory->getData()))){
                                    $quoteSave = $this->quoteRepository->save($quote);
                                    $quote->collectTotals();
                                    $productname =$quote->getLastAddedItem()->getName();
                                    /** @var  \Magento\Quote\Model\Quote\Item  $item */
                                    foreach ($quote->getAllVisibleItems() as $item) {
                                        $data[]=$this->dataHelper->getQuoteItem($item);
                                    }
                                    $response[] = [
                                        'code' => 200,
                                        'status' => true, 
                                        'message' => "Quantity Updated Successfully.",
                                        'data' => $data
                                    ]; 
                                }
                                else if(!isset($cartItems['item_id'])) {
                                   $cartId=$cartItems['quote_id'];
                                   $sku=$cartItems['sku'];              
                                   $quoteItems = $this->quoteItemFactory->create()->getCollection()->addFieldToFilter('quote_id',$cartId)->addFieldToFilter('sku', $sku)->getData();
                                   $totalcount=count($quoteItems);
                                   if( $totalcount == 2){
                                    $quoteQty=$quoteItems[0]['qty']+$quoteItems[1]['qty']+$cartItems['qty'];
                                    if($quoteQty<=$productScalableQty){
                                    $quoteSave = $this->quoteRepository->save($quote);
                                    $productname =$quote->getLastAddedItem()->getName();
                                    $response[] = [
                                        'code' => 200,
                                        'status' => true, 
                                        'message' =>"You added $productname to your shopping cart."
                                    ];
                                    }
                                    else{
                                        $response[] =[
                                            "code" => 400,
                                            "status" => false,
                                            "message" => "The requested product quantity is not available."
                                        ];
                                    }
                                   }else{
                                    $quoteSave = $this->quoteRepository->save($quote);
                                    $productname =$quote->getLastAddedItem()->getName();
                                    $response[] = [
                                        'code' => 200,
                                        'status' => true, 
                                        'message' =>"You added $productname to your shopping cart."
                                    ];
                                   }
                                }
                                else{
                                    $response[] = [
                                        'code' => 400,
                                        'status' => false, 
                                        'message' =>"The Quote Item is not found.Verify the item and try again"
                                    ];
                                }
                            }
                            else{
                                $response[] =[
                                    "code" => 400,
                                    "status" => false,
                                    "message" => "The requested product quantity is not available."
                                ];
                            }   
                        } 
                    }
                    else{
                        $response[] =[
                            "code" => 400,
                            "status" => false,
                            "message" => "The requested product quantity is not available."
                        ];
                    }
                }
                else if($productType = "grouped"){
                    /** @var \Magento\Quote\Model\Quote $quote */
                    $cartId = $cartItem->getQuoteId();
                    if (!$cartId) {
                        $response[] = [
                            'code' => 400,
                            'status' => false, 
                            'message' => "The requested quote doesn't exist"
                        ];
                        return $response;
                    }

                    $quote = $this->quoteRepository->getActive($cartId);
                    $quoteItems = $quote->getItems();
                    $quoteItems[] = $cartItem;
                    $quote->setItems($quoteItems);
                    $productOptions = $cartItem->getData()['product_option']->getData()['extension_attributes'];
                    $extensionAttributes = $productOptions->__toArray();
                    $groupedOptions = $extensionAttributes['grouped_options'];
                    $existQty = [];
                    $qtyCheck = 0;
                    $sourceItem = 0;
                    $minQty = "";
                    foreach($groupedOptions as $groupedOption){
                        $productId = $groupedOption->getId();
                        $productQty = $groupedOption->getQty();
                        $productRepo = $this->productRepository->getById($productId);
                        $productSku = $productRepo->getSku();
                        $minQty = $this->cmsPlpPdp->getMinSaleQtyById($productId);
                        $stockState = $this->getSalableQuantityDataBySku;
                        $qty = $stockState->execute($productSku);
                        if($productQty < $minQty && $productQty != 0){
                            $response[] = [
                                'code' => 400,
                                'status' => false, 
                                'message' => 'The fewest you may purchase is '.$minQty.'.'
                            ];
                            return $response;
                        }
                        if(!empty($qty)){
                            $productScalableQty = $qty[0]['qty'];
                            $quote = $this->quoteRepository->getActive($cartId);
                            $quoteQty = 0;
                            $actualQty = 0;  
                            $actualQty = $productQty;
                            foreach ($quote->getAllVisibleItems() as $item) {
                                if($productSku ==  $item->getSku()){
                                    $quoteQty = $item->getQty();
                                    $actualQty = $productQty + $quoteQty;
                                }
                            }
                            if($actualQty <= $productScalableQty){
                                $existQty[] = 0;
                            }
                            else{
                                $existQty[] = $qtyCheck + 1;
                            }
                        }
                        else{
                            $sourceItem++;
                        }
                    }
                    $existCount = 0;
                    foreach($existQty as $exist){
                        if($exist == 1){
                            $existCount++;
                        }   
                    }
                    if($existCount == 0 && $sourceItem == 0){
                       $i=0;
                       $j=0;                   
                        foreach($groupedOptions as $groupedOption){
                            $productId = $groupedOption->getId();
                            $productQty = $groupedOption->getQty();
                            $productRepo = $this->productRepository->getById($productId);
                            $productSku = $productRepo->getSku();
                            $stockState = $this->getSalableQuantityDataBySku;
                            $qty = $stockState->execute($productSku);
                            $productScalableQty = $qty[0]['qty'];
                            $quoteItems = $this->quoteItemFactory->create()->getCollection()->addFieldToFilter('quote_id',$cartItems['quote_id'])->addFieldToFilter('product_id', $productId)->getData();
                            if(count($quoteItems) == 2){
                                $totalQty=( $quoteItems[0]['qty']+$quoteItems[1]['qty']+ $productQty);
                                if($totalQty<=$productScalableQty){
                                    $i++;
                                }
                                else{
                                    $j++;
                                }
                            }   
                            else{
                                $i++;
                            }
                        }
                        if($i !=0 && $j == 0){
                                $this->quoteRepository->save($quote);
                                $quote->collectTotals();
                                $quote->getLastAddedItem()->getName();
                                $groupedProductData=$this->_productRepository->get($cartItem->getData()['sku']);
                                $productName = $groupedProductData->getName();
                                $response[] = [
                                    'code' => 200,
                                    'status' => true, 
                                    'message' =>"You added $productName to your shopping cart."
                                ];
                                }else{
                                    $response[] =[
                                        "code" => 400,
                                        "status" => false,
                                        "message" => "The requested quantity is not availble."
                                    ];   
                                }
                    }
                    else if($sourceItem != 0){
                        $response[] =[
                            "code" => 400,
                            "status" => false,
                            "message" => "There are no source items with the in stock status"
                        ]; 
                    }
                    else{
                        $response[] =[
                            "code" => 400,
                            "status" => false,
                            "message" => "The requested quantity is not availble."
                        ];   
                    }
                }
            }
            else{
                $response[] =[
                    "code" => 400,
                    "status" => false,
                    "message" => "The requested sku is not availble."
                ]; 
            }
        }
        else{
            $response[] =[
                "code" => 400,
                "status" => false,
                "message" => "Parameter Missing"
            ];
        }
        return $response;
       
    }

    /**
     * @inheritdoc
     */
    public function deleteById($cartId, $itemId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $quoteItem = $quote->getItemById($itemId);
        if (!$quoteItem) {
            $response[] =[
                "code" => 400,
                "status" => false,
                "message" => "Item id not found!,Plese enter correct item id."
            ];
            return $response;
        }    
        $name=$quoteItem->getName();
        $quote->removeItem($itemId);
        $quoteSave = $this->quoteRepository->save($quote);
        $data = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $data[]=$this->dataHelper->getQuoteItem($item);
        }
        $response[] =[
            "code" => 200,
            "status" => true,
            "message" => "$name has been removed.",
            "data" => $data
        ]; 
        return $response;
    }
      /**
     * @param int $wishlistItemId
     * @inheritdoc
     */
    public function wishlistAddToCart(\Magento\Quote\Api\Data\CartItemInterface $cartItem,$wishlistItemId)
    {
        $cartItems =$cartItem->getData();
        if(isset($cartItems['sku']) && isset($cartItems['qty'])){
            $authorizationHeader = $this->http->getHeader('Authorization');
            $tokenParts = explode('Bearer', $authorizationHeader);
            $tokenPayload = trim(array_pop($tokenParts));
            /** @var Token $token */
            $token = $this->tokenFactory->create();
            $token->loadByToken($tokenPayload);
            $customerId = $token->getCustomerId();
            $wishlistCollection = $this->wishlistCollectionFactory->create();
            $wishlistCollection->addFieldToFilter('customer_id', $customerId);
            $wishlistData=$wishlistCollection->getFirstItem()->getItemCollection();
            $wishlistCount=$wishlistData->addFieldToFilter('wishlist_item_id', $wishlistItemId)->getData();
            if(count($wishlistCount)>0){
                    $wishlistData->addFieldToFilter('wishlist_item_id', $wishlistItemId);
                    $count=$wishlistData->addFieldToFilter('wishlist_item_id', $wishlistItemId)->getData();
                    foreach($wishlistData as $item){
                    $wishlistSku=$item->getproduct()->getSku();    
                    }
                if($wishlistSku == $cartItem['sku']){       
                        $cartresponse=$this->addToCart($cartItem);
                        $status=$cartresponse[0]['status'];
                        if($status == true){
                           $this->wishlistRepository->removeItem($wishlistItemId);  
                        }
                        $response=$cartresponse;
                }
                else{
                        $response[] =[
                            "code" => 400,
                            "status" => false,
                            "message" => "Product Sku is not found.Verify the item and try again",
                        ];
                }
            }else{
                    $response[] =[
                        "code" => 400,
                        "status" => false,
                        "message" => "Wishlist Item id is not found.Verify the item and try again",
                    ];
            }
        }else{
                $response[] =[
                    "code" => 400,
                    "status" => false,
                    "message" => "Parameter Missing"
                ];
        }
        return  $response;
  
    }
}