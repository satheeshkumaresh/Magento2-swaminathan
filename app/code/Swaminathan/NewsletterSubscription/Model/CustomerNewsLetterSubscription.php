<?php
namespace Swaminathan\NewsletterSubscription\Model;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;

class CustomerNewsLetterSubscription implements \Swaminathan\NewsletterSubscription\Api\CustomerNewsLetterSubscriptionInterface
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

    public function postNewsLetter($customerId,$isSubscriberStatus)
    {
       
                if ($customerId == null || $customerId == '') {              
                    $response[] = [
                        'code' => 200,
                        'status' => true,
                        'message' => 'Something went wrong with your newsletter subscription'
                    ];
                return $response;
                }           
                $customer = $this->customerRepository->getById($customerId);
                $storeId = $this->storeManager->getStore()->getId();
                $customer->setStoreId($storeId);
                $this->customerRepository->save($customer);
                $isSubscribedStatus = $customer->getExtensionAttributes()->getIsSubscribed();
                if($isSubscriberStatus == false){
                    $isSubscriberStatus = false;
                }
                if($isSubscriberStatus == $isSubscribedStatus)
               {
                        $response[] = [
                            'code' => 200,
                            'status' => true,
                            'message' => 'We have updated your subscription'
                        ];                          
               }else{
                    if($isSubscriberStatus != true)
                        {$this->subscriberFactory->create()->unsubscribeCustomerById($customerId);               
                            $response[] = [
                                'code' => 200,
                                'status' => true,
                                'message' => 'You have successfully unsubscribed!'
                            ];                     
                        }else{$this->subscriberFactory->create()->subscribeCustomerById($customerId);
                            $response[] = [
                                'code' => 200,
                                'status' => true,
                                'message' => 'You have successfully subscribed! Thanks for subscribing to our newsletter!'
                            ];        
                        }        
                    }
         return $response;

    }

}
