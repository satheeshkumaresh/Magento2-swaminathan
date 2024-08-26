<?php
namespace Swaminathan\Checkout\Model;

use Swaminathan\Checkout\Api\GuestBillingAddressManagementInterface;
use Swaminathan\Checkout\Api\BillingAddressManagementInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestBillingAddressManagement implements GuestBillingAddressManagementInterface
{
    private $quoteIdMaskFactory;

    private $billingAddressManagement;

    public function __construct(
        BillingAddressManagementInterface $billingAddressManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->billingAddressManagement = $billingAddressManagement;
    }

    /**
     * {@inheritDoc}
     */
    public function assign($cartId, \Magento\Quote\Api\Data\AddressInterface $address, $useForShipping = false)
    {
        $data = [];
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $addressId = (int)$this->billingAddressManagement->assign($quoteIdMask->getQuoteId(), $address, $useForShipping);
        $data['address_id'] = $addressId;
        $response[] = [
            'code' => 200,
            'status' => true, 
            'message' => "Billing Address Added Successfully.",
            'data' => $data
        ];
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function get($cartId)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->billingAddressManagement->get($quoteIdMask->getQuoteId());
    }
}

