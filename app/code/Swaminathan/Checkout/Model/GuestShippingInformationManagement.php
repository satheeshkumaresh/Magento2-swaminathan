<?php
namespace Swaminathan\Checkout\Model;

class GuestShippingInformationManagement implements \Swaminathan\Checkout\Api\GuestShippingInformationManagementInterface
{
    
    protected $quoteIdMaskFactory;

    protected $shippingInformationManagement;

    public function __construct(
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Swaminathan\Checkout\Api\ShippingInformationManagementInterface $shippingInformationManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->shippingInformationManagement = $shippingInformationManagement;
    }

    /**
     * {@inheritDoc}
     */
    public function saveAddressInformation(
        $cartId,
        \Swaminathan\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->shippingInformationManagement->saveAddressInformation(
            $quoteIdMask->getQuoteId(),
            $addressInformation
        );
    }
}

