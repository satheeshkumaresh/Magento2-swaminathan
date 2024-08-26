<?php
namespace Swaminathan\Checkout\Helper;

use Magento\Quote\Model\Quote\ItemFactory;
use Swaminathan\CmsPlpPdp\Helper\Data as ProductHelper;
use Magento\Catalog\Model\ProductRepository;
use Swaminathan\CmsPlpPdp\Model\CmsPlpPdp;
use Magento\Quote\Model\Quote\Item\OptionFactory as OptionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Swaminathan\Cart\Helper\Data as CartHelper;

class Data
{
    protected $itemFactory;

    protected $productHelper;

    protected $productRepository;

    protected $cmsPlpPdp;

    protected $optionFactory;

    protected $scopeConfig;

    protected $storeManager;

    public function __construct(
        ItemFactory $itemFactory,
        ProductHelper $productHelper,
        ProductRepository $productRepository,
        CmsPlpPdp $cmsPlpPdp,
        OptionFactory $optionFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CartHelper $cartHelper
    )
    {
        $this->itemFactory = $itemFactory;
        $this->productHelper = $productHelper; 
        $this->productRepository = $productRepository;
        $this->cmsPlpPdp = $cmsPlpPdp;
        $this->optionFactory = $optionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->cartHelper = $cartHelper;
    }
    public function getSummaryTotal($paymentDetails){
        $maximumAmount = "";
        $errorMessage = "";
        $active = $this->scopeConfig->getValue(
            'sales/maximum_order/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
        );
        if($active == 1){
            $maximumAmount = $this->scopeConfig->getValue(
                'sales/maximum_order/amount',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            );
            $errorMessage = $this->scopeConfig->getValue(
                'sales/maximum_order/error_message',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            );
        }
        $data = [];
        $paymentMethodsInfo = [];
        if(isset($paymentDetails['payment_methods'])){
            $paymentInfo = $paymentDetails['payment_methods'];
            foreach($paymentInfo as $payment){
                $paymentMethodInfo['title'] = $payment->getTitle();
                $paymentMethodInfo['code'] = $payment->getCode();
                $paymentMethodsInfo[] = $paymentMethodInfo;
            }   
        }
        if(isset($paymentDetails['totals'])){
            $totalInfo = $paymentDetails['totals']->getData();
        }
        else{
            $totalInfo = $paymentDetails;
        }
        // checkout item lists
        $itemDatas = $totalInfo['items'];
        $quoteItemData = [];
        $groupedProductId = "";
        $quoteItems = [];
        foreach($itemDatas as $itemData){
            $itemId = $itemData->getItemId();
            $options = $this->optionFactory->create()->getCollection();
            $options->addFieldToFilter('item_id', $itemId);
            foreach($options->getData() as $option){
                if($option['code'] == "product_type" && $option['value'] == "grouped"){
                    $groupedProductId = $option['product_id'];
                }
            }
            $quoteItem = $this->itemFactory->create()->load($itemId, 'item_id')->getData();
            $productData = $this->productRepository->getById($quoteItem['product_id']);
            $quoteItemData['item_id'] = $quoteItem['item_id'];
            $quoteItemData['quote_id'] = $quoteItem['quote_id'];
            $quoteItemData['product_id'] = $quoteItem['product_id'];
            $quoteItemData['sku'] = $quoteItem['sku'];
            $quoteItemData['name'] = $quoteItem['name'];
            $quoteItemData['category_name'] = $this->cartHelper->getCategoriesName($quoteItem['product_id']);
            $quoteItemData['color'] = $productData->getResource()->getAttribute(CmsPlpPdp::ATTRIBUTE_CODE)->getFrontend()->getValue($productData);
            $quoteItemData['weight'] = $this->productHelper->getSizeAttributeValue($productData);
            $offerData = $this->cmsPlpPdp->getSpecialPriceByProductId($quoteItem['product_id']);
            $tagData = $this->cmsPlpPdp->getNewFromToDate($quoteItem['product_id']);
            $tag['on_offer'] = $offerData['tag'];
            $tag['new_arrival'] = $tagData['tag'];
            $quoteItemData['tags'] = $tag;
            $quoteItemData['special_price'] = $this->productHelper->getFormattedPrice($offerData['special_price']);
            $quoteItemData['qty'] = $this->productHelper->getFormattedPrice($quoteItem['qty']);
            $quoteItemData['price'] = $this->productHelper->getFormattedPrice($quoteItem['price']);
            $quoteItemData['discount_percent'] = $this->productHelper->getFormattedPrice($quoteItem['discount_percent']);
            $quoteItemData['discount_amount'] = $this->productHelper->getFormattedPrice($quoteItem['discount_amount']);
            $quoteItemData['tax_percent'] = $this->productHelper->getFormattedPrice($quoteItem['tax_percent']);
            $quoteItemData['tax_amount'] = $this->productHelper->getFormattedPrice($quoteItem['tax_amount']);
            $quoteItemData['row_total'] = $this->productHelper->getFormattedPrice($quoteItem['row_total']);
            $quoteItemData['row_total_with_discount'] = $this->productHelper->getFormattedPrice($quoteItem['row_total_with_discount']);
            $quoteItemData['price_incl_tax'] = $this->productHelper->getFormattedPrice($quoteItem['price_incl_tax']);
            $quoteItemData['row_total_incl_tax'] = $this->productHelper->getFormattedPrice($quoteItem['row_total_incl_tax']);
            $quoteItemData['discount_tax_compensation_amount'] = $this->productHelper->getFormattedPrice($quoteItem['discount_tax_compensation_amount']);
            $quoteItemData['product_type'] = $quoteItem['product_type'];
            if($groupedProductId != ""){
                $quoteItemData['product_url'] = $this->productHelper->getProductRewriteUrl($groupedProductId);
            }
            else{
                $quoteItemData['product_url'] = $this->productHelper->getProductRewriteUrl($quoteItem['product_id']);
            }
            $quoteItemData['product_image'] = $this->productHelper->getProductImage($quoteItem['product_id']); 
            $quoteItems[] = $quoteItemData;
        }
        // total segment 
        $totalSegments = [];
        // subtotal section
        $subTotalSec = $totalInfo['total_segments']['subtotal']->getData();
        $subTotal['code'] = $subTotalSec['code'];
        $subTotal['title'] = $subTotalSec['title'];
        $subTotal['value'] = $this->productHelper->getFormattedPrice($subTotalSec['value']);
        $totalSegments['subtotal'] = $subTotal;
        // shipping section
        $shipInfo = $totalInfo['total_segments']['shipping']->getData();
        $shippingInfo['code'] = $shipInfo['code'];
        $shippingInfo['title'] = $shipInfo['title'];
        $shippingInfo['value'] = $this->productHelper->getFormattedPrice($shipInfo['value']);
        $totalSegments['shipping'] = $shippingInfo;
        // tax section
        $taxInfo = $totalInfo['total_segments']['tax']->getData();
        $taxDetail['code'] = $taxInfo['code'];
        $taxDetail['title'] = $taxInfo['title'];
        $taxDetail['value'] = $this->productHelper->getFormattedPrice($taxInfo['value']);
        $taxExtensionAttributes = $taxInfo['extension_attributes']->__toArray();
        $taxDetail['extension_attributes'] = $taxExtensionAttributes;
        $totalSegments['tax'] = $taxDetail;
         // grand total section
        $grandTotal = $totalInfo['total_segments']['grand_total']->getData();
        $grandTotalInfo['code'] = $grandTotal['code'];
        $grandTotalInfo['title'] = $grandTotal['title'];
        $grandTotalInfo['value'] = $this->productHelper->getFormattedPrice($grandTotal['value']);
        $totalSegments['grand_total'] = $grandTotalInfo;
        $finalTotal = $this->productHelper->getFormattedPrice($grandTotal['value']);
        if($finalTotal < $maximumAmount){
            $maximumAmount = "";
            $errorMessage = "";
        }
        $maximumAmountInfo['maximum_amount'] = $maximumAmount;
        $maximumAmountInfo['error_message'] = $errorMessage;
        $totalSegments['maximum_order_amount'] = $maximumAmountInfo;
        $totals = [];
        if(!empty($paymentMethodsInfo)){
            $data['payment_methods'] =  $paymentMethodsInfo;
        }
        $totals['subtotal'] = $this->productHelper->getFormattedPrice($totalInfo['subtotal']);
        $totals['subtotal_with_discount'] = $this->productHelper->getFormattedPrice($totalInfo['subtotal_with_discount']);
        $totals['tax_amount'] = $this->productHelper->getFormattedPrice($totalInfo['tax_amount']);
        $totals['shipping_amount'] = $this->productHelper->getFormattedPrice($totalInfo['shipping_amount']);
        $totals['shipping_tax_amount'] = $this->productHelper->getFormattedPrice($totalInfo['shipping_tax_amount']);
        $totals['discount_amount'] = $this->productHelper->getFormattedPrice($totalInfo['discount_amount']);
        $totals['grand_total'] = $this->productHelper->getFormattedPrice($totalInfo['grand_total']);
        $totals['shipping_discount_amount'] = $this->productHelper->getFormattedPrice($totalInfo['shipping_discount_amount']);
        $totals['subtotal_incl_tax'] = $this->productHelper->getFormattedPrice($totalInfo['subtotal_incl_tax']);
        $totals['shipping_incl_tax'] = $this->productHelper->getFormattedPrice($totalInfo['shipping_incl_tax']);
        $couponCode = "";
        if($totalInfo['coupon_code'] != null){
            $couponCode = $totalInfo['coupon_code'];
        }
        $totals['coupon_code'] = $couponCode;
        $totals['items_qty'] = $this->productHelper->getFormattedPrice($totalInfo['items_qty']);
        $totals['base_currency_code'] = $totalInfo['base_currency_code'];
        $totals['quote_currency_code'] = $totalInfo['quote_currency_code'];
        $totals['total_segments'] = $totalSegments;
        $totals['items'] = $quoteItems;
        $data['total'] = $totals;
        return $data;
    }
}
