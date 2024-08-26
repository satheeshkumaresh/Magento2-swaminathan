<?php
namespace Swaminathan\Cart\Model\GuestCart;

use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\Data\AddressInterface;
use Swaminathan\Cart\Api\GuestShipmentEstimationInterface;
use Swaminathan\Cart\Api\ShipmentEstimationInterface;
use Swaminathan\Cart\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * Shipping method management class for guest carts.
 */
class GuestShippingMethodManagement implements
    \Swaminathan\Cart\Api\GuestShippingMethodManagementInterface,
    \Magento\Quote\Model\GuestCart\GuestShippingMethodManagementInterface,
    GuestShipmentEstimationInterface
{
    /**
     * @var ShippingMethodManagementInterface
     */
    private $shippingMethodManagement;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var ShipmentEstimationInterface
     */
    private $shipmentEstimationManagement;

    /**
     * Constructs a shipping method read service object.
     *
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        ShippingMethodManagementInterface $shippingMethodManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function get($cartId)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->shippingMethodManagement->get($quoteIdMask->getQuoteId());
    }

    /**
     * {@inheritDoc}
     */
    public function getList($cartId)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->shippingMethodManagement->getList($quoteIdMask->getQuoteId());
    }

    /**
     * {@inheritDoc}
     */
    public function set($cartId, $carrierCode, $methodCode)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->shippingMethodManagement->set($quoteIdMask->getQuoteId(), $carrierCode, $methodCode);
    }

    /**
     * {@inheritDoc}
     */
    public function estimateByAddress($cartId, \Magento\Quote\Api\Data\EstimateAddressInterface $address)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->shippingMethodManagement->estimateByAddress($quoteIdMask->getQuoteId(), $address);
    }

    /**
     * @inheritdoc
     */
    public function estimateByExtendedAddress($cartId, AddressInterface $address)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->getShipmentEstimationManagement()
            ->estimateByExtendedAddress((int) $quoteIdMask->getQuoteId(), $address);
    }

    /**
     * Get shipment estimation management service
     * @return ShipmentEstimationInterface
     * @deprecated 100.0.7
     */
    private function getShipmentEstimationManagement()
    {
        if ($this->shipmentEstimationManagement === null) {
            $this->shipmentEstimationManagement = ObjectManager::getInstance()
                ->get(ShipmentEstimationInterface::class);
        }
        return $this->shipmentEstimationManagement;
    }
}

