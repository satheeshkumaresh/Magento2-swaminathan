<?php
namespace Swaminathan\Cart\Model;

use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Swaminathan\Checkout\Helper\Data as TotalSummary;

class TotalsInformationManagement implements \Swaminathan\Cart\Api\TotalsInformationManagementInterface
{
   
    protected $cartTotalRepository;

    protected $cartRepository;

    protected $totalSummary;

    public function __construct(
        TotalSummary $totalSummary,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalRepository
    ) {
        $this->totalSummary = $totalSummary;
        $this->cartRepository = $cartRepository;
        $this->cartTotalRepository = $cartTotalRepository;
    }

    /**
     * @inheritDoc
     */
    public function calculate(
        $cartId,
        TotalsInformationInterface $addressInformation
    ) {
        $shipping = "";
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cartRepository->get($cartId);
        $this->validateQuote($quote);

        if ($quote->getIsVirtual()) {
            $quote->setBillingAddress($addressInformation->getAddress());
        } else {
            $quote->setShippingAddress($addressInformation->getAddress());
            if ($addressInformation->getShippingCarrierCode() && $addressInformation->getShippingMethodCode()) {
                $shippingMethod = implode(
                    '_',
                    [$addressInformation->getShippingCarrierCode(), $addressInformation->getShippingMethodCode()]
                );
                $quoteShippingAddress = $quote->getShippingAddress();
                if ($quoteShippingAddress->getShippingMethod() &&
                    $quoteShippingAddress->getShippingMethod() !== $shippingMethod
                ) {
                    $quoteShippingAddress->setShippingAmount(0);
                    $quoteShippingAddress->setBaseShippingAmount(0);
                }
                $quoteShippingAddress->setCollectShippingRates(true)
                    ->setShippingMethod($shippingMethod);
            }
        }
        $quote->collectTotals();
        $totalInfo =$this->cartTotalRepository->get($cartId)->getData();
        $data = $this->totalSummary->getSummaryTotal($totalInfo);
        $response[] = [
            'code' => 200,
            'status' => true, 
            'data' => $data
        ];
        return $response;
    }

    /**
     * Check if quote have items.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->getItemsCount() === 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Totals calculation is not applicable to empty cart')
            );
        }
    }
}

