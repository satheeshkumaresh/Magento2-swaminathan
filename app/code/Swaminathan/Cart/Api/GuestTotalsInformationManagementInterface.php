<?php
namespace Swaminathan\Cart\Api;

interface GuestTotalsInformationManagementInterface
{
    /**
     * Calculate quote totals based on address and shipping method.
     *
     * @param string $cartId
     * @param \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
     * @return \Magento\Quote\Api\Data\TotalsInterface
     */
    public function calculate(
        $cartId,
        \Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
    );
}
