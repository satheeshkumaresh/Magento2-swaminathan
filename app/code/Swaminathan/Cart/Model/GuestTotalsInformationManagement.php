<?php
namespace Swaminathan\Cart\Model;

class GuestTotalsInformationManagement implements \Swaminathan\Cart\Api\GuestTotalsInformationManagementInterface
{
    /**
     * @var \Magento\Quote\Model\QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var \Swaminathan\Cart\Api\TotalsInformationManagementInterface
     */
    protected $totalsInformationManagement;

    /**
     * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Swaminathan\Cart\Api\TotalsInformationManagementInterface $totalsInformationManagement
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
        \Swaminathan\Cart\Api\TotalsInformationManagementInterface $totalsInformationManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->totalsInformationManagement = $totalsInformationManagement;
    }

    /**
     * {@inheritDoc}
     */
    public function calculate(
        $cartId,
        \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
    ) {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->totalsInformationManagement->calculate(
            $quoteIdMask->getQuoteId(),
            $addressInformation
        );
    }
}
