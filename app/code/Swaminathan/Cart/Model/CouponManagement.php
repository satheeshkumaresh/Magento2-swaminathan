<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Swaminathan\Cart\Model;

use Magento\Framework\Exception\LocalizedException;
use \Magento\Quote\Api\CouponManagementInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Model\Coupon;
use Magento\SalesRule\Model\RuleRepository;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Coupon management object.
 */
class CouponManagement implements CouponManagementInterface
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Coupon.
     *
     * @var \Magento\SalesRule\Model\Coupon
     */
    protected $coupon;

    protected $ruleRepository;

    protected $timezoneInterface;

    /**
     * Constructs a coupon read service object.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository Quote repository.
     * @param Magento\SalesRule\Model\Coupon $coupon
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\SalesRule\Model\Coupon $coupon,
        RuleRepository $ruleRepository,
        TimezoneInterface $timezoneInterface
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->coupon = $coupon;
        $this->ruleRepository = $ruleRepository;
        $this->timezoneInterface = $timezoneInterface;
    }

    /**
     * @inheritDoc
     */
    public function get($cartId)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        return $quote->getCouponCode();
    }

    /**
     * @inheritDoc
     */
    public function set($cartId, $couponCode)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "data" => "The Cart doesn't contain products."
            ];
            return $response;
        }
        if (!$quote->getStoreId()) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "data" => "Cart isn't assigned to correct store."
            ];
            return $response;
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);

        try {
            $rule =  $this->coupon->loadByCode($couponCode);
            $ruleId = $rule->getRuleId();
            $salesRule = $this->ruleRepository->getById($ruleId);
            $startDate = $salesRule->getFromDate();
            $endDate = $salesRule->getToDate();
            $todayDate = $this->timezoneInterface
                                            ->date()
                                            ->format('Y-m-d');
            if (!empty($ruleId)) {
                if($startDate <= $todayDate && $endDate >= $todayDate){
                    $quote->setCouponCode($couponCode);
                    $this->quoteRepository->save($quote->collectTotals());
                    $couponData = $quote->getData();
                    $data['customer_id'] = "";
                    if($couponData['customer_id'] != null){
                        $data['customer_id'] = $couponData['customer_id'];
                    }
                    if($couponData['customer_email'] != null){
                        $data['customer_email'] = $couponData['customer_email'];
                    }
                    $data['items_count'] = $couponData['items_count'];
                    $data['items_qty'] = $couponData['items_qty'];
                    $data['shipping_amount'] = $couponData['shipping_amount'];
                    $data['base_shipping_amount'] = $couponData['base_shipping_amount'];
                    $data['shipping_description'] = $couponData['shipping_description'];
                    $data['base_shipping_amount'] = $couponData['base_shipping_amount'];
                    $discountPrice = $couponData['subtotal'] - $couponData['subtotal_with_discount'];
                    $data['discount_price'] = $discountPrice;
                    $data['subtotal'] = $couponData['subtotal'];
                    $data['subtotal_with_discount'] = $couponData['subtotal_with_discount'];
                    $data['customer_email'] = $couponData['customer_email'];
                    $data['customer_firstname'] = $couponData['customer_firstname'];
                    $data['customer_lastname'] = $couponData['customer_lastname'];
                    $data['base_currency_code'] = $couponData['base_currency_code'];
                    $data['store_currency_code'] = $couponData['store_currency_code'];
                    $data['quote_currency_code'] = $couponData['quote_currency_code'];
                    $data['checkout_method'] = "";
                    if($couponData['checkout_method'] != null){
                        $data['checkout_method'] = $couponData['checkout_method'];
                    }
                    if($couponData['customer_email'] != null){
                        $data['customer_email'] = $couponData['customer_email'];
                    }
                    $data['grand_total'] = $couponData['grand_total'];
                    $response[] = [
                        'code' => 200,
                        'status' => true, 
                        'message' => "Coupon added successfully.",
                        'data' => $data
                    ];
                    return $response; 
                }
                else{
                    $response[] = [
                        'code' => 400,
                        'status' => false, 
                        'message' => 'The coupon code "'.$couponCode.'" is expired.'
                    ];
                    return $response;  
                }
            }
            else{
                $response[] = [
                    'code' => 400,
                    'status' => false, 
                    'message' => 'The coupon code "'.$couponCode.'" is not valid.'
                ];
                return $response; 
            }
        } catch (LocalizedException $e) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => "This coupon code is invalid."
            ];
            return $response;
        } catch (\Exception $e) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => "The coupon code couldn't be applied. Verify the coupon code and try again."
            ];
            return $response;
        }
        if ($quote->getCouponCode() != $couponCode) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => "The coupon code isn't valid. Verify the code and try again."
            ];
            return $response;
        }
    }

    /**
     * @inheritDoc
     */
    public function remove($cartId)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "data" => "The Cart doesn't contain products."
            ];
            return $response;
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);
        try {
            $quote->setCouponCode('');
            $this->quoteRepository->save($quote->collectTotals());
            $couponData = $quote->getData();
            if($couponData['customer_id'] != null){
                $data['customer_id'] = $couponData['customer_id'];
            }
            if($couponData['customer_email'] != null){
                $data['customer_email'] = $couponData['customer_email'];
            }
            $data['items_count'] = $couponData['items_count'];
            $data['items_qty'] = $couponData['items_qty'];
            $data['subtotal'] = $couponData['subtotal'];
            $data['subtotal_with_discount'] = $couponData['subtotal_with_discount'];
            $data['grand_total'] = $couponData['grand_total'];
            $response[] = [
                'code' => 200,
                'status' => true, 
                'message' => "Coupon removed successfully.",
                'data' => $data
            ]; 
            return $response;
        } catch (\Exception $e) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "data" => "The coupon code couldn't be deleted. Verify the coupon code and try again."
            ];
            return $response;
        }
        if ($quote->getCouponCode() != '') {
            $response[] = [
                "code" => 400,
                "status" => false,
                "data" => "The coupon code couldn't be deleted. Verify the coupon code and try again."
            ];
            return $response;
        }
    }
}
