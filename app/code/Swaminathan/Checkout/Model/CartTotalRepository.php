<?php
namespace Swaminathan\Checkout\Model;

use Magento\Quote\Api;
use Magento\Quote\Api\CartRepositoryInterface;
use Swaminathan\Checkout\Api\CartTotalRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Quote\Model\Cart\Totals\ItemConverter;
use Magento\Quote\Api\CouponManagementInterface;
use Magento\Quote\Api\Data\TotalsInterface as QuoteTotalsInterface;
use Magento\Quote\Model\Cart\TotalsConverter;
use Swaminathan\Checkout\Helper\Data as TotalSummary;

class CartTotalRepository implements CartTotalRepositoryInterface
{
    private $totalsFactory;

    private $quoteRepository;

    private $dataObjectHelper;

    private $itemConverter;

    protected $couponService;

    protected $totalsConverter;

    protected $totalSummary;

    public function __construct(
        Api\Data\TotalsInterfaceFactory $totalsFactory,
        CartRepositoryInterface $quoteRepository,
        DataObjectHelper $dataObjectHelper,
        CouponManagementInterface $couponService,
        TotalsConverter $totalsConverter,
        ItemConverter $converter,
        TotalSummary $totalSummary
    ) {
        $this->totalsFactory = $totalsFactory;
        $this->quoteRepository = $quoteRepository;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->couponService = $couponService;
        $this->totalsConverter = $totalsConverter;
        $this->itemConverter = $converter;
        $this->totalSummary = $totalSummary;
    }

    /**
     * @inheritdoc
     */
    public function get($cartId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if ($quote->isVirtual()) {
            $quote->collectTotals();
            $addressTotalsData = $quote->getBillingAddress()->getData();
            $addressTotals = $quote->getBillingAddress()->getTotals();
        } else {
            $addressTotalsData = $quote->getShippingAddress()->getData();
            $addressTotals = $quote->getShippingAddress()->getTotals();
        }
        unset($addressTotalsData[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);

        /** @var QuoteTotalsInterface $quoteTotals */
        $quoteTotals = $this->totalsFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $quoteTotals,
            $addressTotalsData,
            QuoteTotalsInterface::class
        );
        $items = array_map([$this->itemConverter, 'modelToDataObject'], $quote->getAllVisibleItems());
        $calculatedTotals = $this->totalsConverter->process($addressTotals);
        $quoteTotals->setTotalSegments($calculatedTotals);

        $amount = $quoteTotals->getGrandTotal() - $quoteTotals->getTaxAmount();
        $amount = $amount > 0 ? $amount : 0;
        $quoteTotals->setCouponCode($this->couponService->get($cartId));
        $quoteTotals->setGrandTotal($amount);
        $quoteTotals->setItems($items);
        $quoteTotals->setItemsQty($quote->getItemsQty());
        $quoteTotals->setBaseCurrencyCode($quote->getBaseCurrencyCode());
        $quoteTotals->setQuoteCurrencyCode($quote->getQuoteCurrencyCode());
        $totalSummaryInfo = $this->totalSummary->getSummaryTotal($quoteTotals->getData());
        $response[] = [
            'code' => 200,
            'status' => true, 
            'data' => $totalSummaryInfo
        ];
        return $response;
    }
}

