<?php
namespace Swaminathan\Checkout\Model;

use Magento\Quote\Api\CartTotalRepositoryInterface;
use Swaminathan\Checkout\Api\GuestCartTotalRepositoryInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Swaminathan\Checkout\Helper\Data as TotalSummary;

class GuestCartTotalRepository implements GuestCartTotalRepositoryInterface
{
    protected $quoteIdMaskFactory;

    protected $cartTotalRepository;

    protected $totalSummary;

    public function __construct(
        CartTotalRepositoryInterface $cartTotalRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        TotalSummary $totalSummary
    ) {
        $this->cartTotalRepository = $cartTotalRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->totalSummary = $totalSummary;
    }

    /**
     * {@inheritDoc}
     */
    public function get($cartId)
    {
        $totalSummaryInfo = [];
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $totalSummaryInfo = $this->totalSummary->getSummaryTotal($this->cartTotalRepository->get($quoteIdMask->getQuoteId())->getData());
        $response[] = [
            'code' => 200,
            'status' => true, 
            'data' => $totalSummaryInfo
        ];
        return $response;
    }
}
