<?php
namespace Swaminathan\NewsletterSubscription\Model;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;

class NewsLetterSubscription implements \Swaminathan\NewsletterSubscription\Api\NewsLetterSubscriptionInterface
{

    /**
     * @var \Magento\Framework\App\RequestInterface
    */

    protected $_request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var \Magento\Newsletter\Model\Subscriber
     */
    protected $_subscriber;

    /**
     * Initialize dependencies.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CustomerRepository $customerRepository
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     * @param \Magento\Newsletter\Model\SubscriptionManagerInterface;
     */

   public function __construct(    
    \Magento\Framework\App\RequestInterface $request,
    \Magento\Store\Model\StoreManagerInterface $storeManager,
    CustomerRepository $customerRepository,
    \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
     \Magento\Newsletter\Model\Subscriber $subscriber
       )
    {
        $this->_request = $request;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->subscriberFactory = $subscriberFactory;
        $this->_subscriber      = $subscriber;
       
    }

    /**
     * {@inheritdoc}
    */

    public function postNewsLetter($customerId)
    {
       
        if ($customerId == null || $customerId == '') {

            return 'Something went wrong with your newsletter subscription.';

        }else
        {
            try {     
            
                $customer = $this->customerRepository->getById($customerId);
                $storeId = $this->storeManager->getStore()->getId();
                $customer->setStoreId($storeId);
                $this->customerRepository->save($customer);
                $isSubscribedState = $customer->getExtensionAttributes()->getIsSubscribed();
                if (!$isSubscribedState)
                {
                   
                    $this->subscriberFactory->create()->subscribeCustomerById($customerId);
                    return 'You have successfully subscribed! Thanks for subscribing to our newsletter!';

                } else {
                   
                    $this->subscriberFactory->create()->unsubscribeCustomerById($customerId);               
                    return 'You have successfully unsubscribed!';
                    
                }
           

            } catch (\Exception $e) {

                return 'Something went wrong with your newsletter subscription.';

            }
        }


    }

}
