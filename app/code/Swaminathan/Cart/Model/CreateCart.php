<?php
namespace Swaminathan\Cart\Model;

use Swaminathan\Checkout\Model\QuoteManagement;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Swaminathan\Customer\Model\CustomerAddress;

class CreateCart  implements \Swaminathan\Cart\Api\CreateCartInterface
{
    protected $storeManager;
    protected $quoteManagement;
    protected $quoteRepository;
    protected $customerRepository;
    protected $quoteFactory;
    protected $customerAddress;
    public function __construct(
        QuoteManagement $quoteManagement,
        StoreManagerInterface $storeManager,
        CartRepositoryInterface $quoteRepository,
        CustomerRepositoryInterface $customerRepository,
        QuoteFactory $quoteFactory,
        CustomerAddress $customerAddress
    ) {
       $this->quoteManagement = $quoteManagement;
       $this->storeManager = $storeManager;
       $this->quoteRepository = $quoteRepository;
       $this->customerRepository = $customerRepository;
       $this->quoteFactory = $quoteFactory;
       $this->customerAddress = $customerAddress;
    }

    public function createEmptyCustomerCart(){
        $customerId = $this->customerAddress->getCustomerId();
        $storeId = $this->storeManager->getStore()->getStoreId();
        $quote = $this->createCustomerCart($customerId, $storeId);
        try {
            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => "The quote can't be created."
            ];
            return $response;
        }
        return (int)$quote->getId();
    }

    public function createCustomerCart($customerId, $storeId)
    {
        try {
            $quote = $this->quoteFactory->create()
                ->getCollection()
                ->addFieldToFIlter('customer_id', $customerId)
                ->addFieldToFIlter('is_active', 1);
            if(!empty($quote->getData())){
                $quote = $this->quoteRepository->getActiveForCustomer($customerId);
            }
            else{
                $customer = $this->customerRepository->getById($customerId);
                /** @var Quote $quote */
                $quote = $this->quoteFactory->create();
                $quote->setStoreId($storeId);
                $quote->setCustomer($customer);
                $quote->setCustomerIsGuest(0);
                $quote->save();
            }
        } catch (NoSuchEntityException $e) {
            $customer = $this->customerRepository->getById($customerId);
            /** @var Quote $quote */
            $quote = $this->quoteFactory->create();
            $quote->setStoreId($storeId);
            $quote->setCustomer($customer);
            $quote->setCustomerIsGuest(0);
        }
        return $quote;
    }
}