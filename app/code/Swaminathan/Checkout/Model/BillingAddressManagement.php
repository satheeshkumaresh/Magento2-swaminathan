<?php
namespace Swaminathan\Checkout\Model;

use Magento\Framework\Exception\InputException;
use Magento\Quote\Model\Quote\Address\BillingAddressPersister;
use Psr\Log\LoggerInterface as Logger;
use Swaminathan\Checkout\Api\BillingAddressManagementInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\QuoteAddressValidator;
use Swaminathan\Checkout\Model\Address;

class BillingAddressManagement implements BillingAddressManagementInterface
{
    protected $address;
    
    protected $addressValidator;

    protected $logger;

    protected $quoteRepository;

    protected $addressRepository;

    private $shippingAddressAssignment;

    public function __construct(
        Address $address,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        QuoteAddressValidator $addressValidator,
        Logger $logger,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->address = $address;
        $this->addressValidator = $addressValidator;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
        $this->addressRepository = $addressRepository;
    }

    /**
     * @inhersitdoc
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function assign($cartId, \Magento\Quote\Api\Data\AddressInterface $address, $useForShipping = false)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $address->setCustomerId($quote->getCustomerId());
        $quote->removeAddress($quote->getBillingAddress()->getId());
        $quote->setBillingAddress($address);
        try {
            $this->getShippingAddressAssignment()->setAddress($quote, $address, $useForShipping);
            $quote->setDataChanges(true);
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('The address failed to save. Verify the address and try again.'));
        }
        $addressId = $quote->getBillingAddress()->getId();
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
     * @inheritdoc
     */
    public function get($cartId)
    {
        return $this->address->getAddress($cartId);
    }

    /**
     * Get shipping address assignment
     *
     * @return \Magento\Quote\Model\ShippingAddressAssignment
     * @deprecated 101.0.0
     */
    private function getShippingAddressAssignment()
    {
        if (!$this->shippingAddressAssignment) {
            $this->shippingAddressAssignment = ObjectManager::getInstance()
                ->get(\Magento\Quote\Model\ShippingAddressAssignment::class);
        }
        return $this->shippingAddressAssignment;
    }
}
