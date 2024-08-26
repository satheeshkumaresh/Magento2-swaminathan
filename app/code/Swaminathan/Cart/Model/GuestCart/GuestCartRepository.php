<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Swaminathan\Cart\Model\GuestCart;

use Swaminathan\Cart\Api\GuestCartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Swaminathan\Cart\Helper\Data as DataHelper;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;

/**
 * Cart Repository class for guest carts.
 */
class GuestCartRepository implements GuestCartRepositoryInterface
{
    /**
     * @var UrlHelper
     */
    protected $urlHelper;
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;
    /**
     * @var ProductHelper
     */
    protected $productHelper;
     /**
     * @var DataHelper
     */
    protected $dataHelper;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
     /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * Initialize dependencies.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param ProductRepositoryInterface $productRepository
     * @param DataHelper $dataHelper
     * @param ProductHelper $productHelper
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param UrlHelper $urlHelper
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        ProductRepositoryInterface $productRepository,
        DataHelper $dataHelper,
        ProductHelper $productHelper,
        CartTotalRepositoryInterface $cartTotalRepository,
        UrlHelper $urlHelper
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->dataHelper = $dataHelper;
        $this->productHelper = $productHelper;
        $this->cartTotalRepository = $cartTotalRepository;
        $this->urlHelper = $urlHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getCartDetails($cartId)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        if(!$cartId || !$quoteIdMask->getmaskedId() ){
            $response[] =[
                "code" => 400,
                "status" => false,
                "message" => "Cart details not found."
            ];          
        }
        else{
            $data=[];
            $crossSellProducts = [];
            $quote=$this->quoteRepository->get($quoteIdMask->getQuoteId());
            $quote->collectTotals();
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
            $quoteItems = $this->cartTotalRepository->get($quoteIdMask->getQuoteId())->getData();
            $cartSubTotal = $this->productHelper->getFormattedPrice($quoteItems['subtotal']);
            $displayCartSubTotal = $this->productHelper->INDMoneyFormat($quoteItems['subtotal']);
            $itemsQty = $this->productHelper->getFormattedPrice($quoteItems['items_qty']);
            $count = 0;
            foreach($data as $guestCartItems){
                if(isset($guestCartItems['qty'])){
                    $count = $count + $guestCartItems['qty'];
                }
            }
            if(!empty($data)){               
                $response[] =[
                    "code" => 200,
                    "status" => true,
                    "total_no_of_items" => $count,
                    "sub_total" => $cartSubTotal,
                    "display_sub_total" => $displayCartSubTotal,
                    "items_qty" => $itemsQty,
                    "data" => $data,
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
         }       
        return $response;
    }
}
