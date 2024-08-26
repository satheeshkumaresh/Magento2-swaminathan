<?php

namespace Swaminathan\NewsletterSubscription\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;


class SubscriberManagement extends \Magento\Newsletter\Model\Subscriber implements \Swaminathan\NewsletterSubscription\Api\SubscriberManagementInterface
{

    protected $subscriberModel;

    protected $subscribersCollection;
    protected $emailValidator;

    protected $collectionProcessor;

    public function __construct(
        \Magento\Newsletter\Model\Subscriber $subscriberModel,
        \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection $subscribersCollection,
        \Magento\Framework\Validator\EmailAddress $emailValidator,
        \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor,
        CollectionFactory $CollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CustomerRepository $customerRepository,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        )
    {
        $this->subscriberModel = $subscriberModel;
        $this->subscribersCollection = $subscribersCollection;
        $this->emailValidator = $emailValidator;
        $this->collectionProcessor = $collectionProcessor;
        $this->collectionFactory=$CollectionFactory;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * @param \Swaminathan\NewsletterSubscription\Api\Data\SubscriberInterface $subscriber
     * @return \Swaminathan\NewsletterSubscription\Api\Data\SubscriberInterface
     * @throws LocalizedException
     */
    public function postSubscriber($subscriber)
    {
        $email = (string)$subscriber->getSubscriberEmail();
        $customerCollectionFactory = $this->collectionFactory->create()->addAttributeToFilter('email',$email);
        $count=$customerCollectionFactory->getSize(); 
            if (!$this->emailValidator->isValid($email)) {
                $response[] = [
                    'code' => 400,
                    'status' => false,
                    'message' => 'Please enter a valid email address.'
                ];
                return $response; 
             }
            $subscriberModel = $this->subscriberModel->loadByEmail($email);
            if ($subscriberModel->getId()
                && (int) $subscriberModel->getSubscriberStatus() === \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
            ) { 
                $response[] = [
                    'code' => 400,
                    'status' => false,
                    'message' => 'This email address is already subscribed.'
                ];
                return $response; 
              }
            if($count==1){
                $customerId = $customerCollectionFactory->getData()[0]['entity_id'];
                $customer = $this->customerRepository->getById($customerId);
                $storeId = $this->storeManager->getStore()->getId();
                $customer->setStoreId($storeId);
                $this->customerRepository->save($customer);
                $isSubscribedState = $customer->getExtensionAttributes()->getIsSubscribed();
                if (!$isSubscribedState)
                {
                
                    $this->subscriberFactory->create()->subscribeCustomerById($customerId);
    
                } 
                $response[] = [
                    'code' => 200,
                    'status' => true,
                    'message' => 'You have successfully subscribed! Thanks for subscribing to our newsletter!'
                ];
          }
        else {
            $status = (int) $subscriberModel->subscribe($email);
            $subscriber->addData($subscriberModel->getData());
            $response[] = [
                'code' => 200,
                'status' => true,
                'message' => 'You have successfully subscribed!'
            ];
                
            }
   
        return $response;
       
    }


    public function postConfirm($id, $confirmCode){
        $subscriber = $this->subscriberModel->load($id);

        if ($subscriber->getId() && $subscriber->getCode()) {
            if ($subscriber->confirm($confirmCode)) {
                return $subscriber;
            } else {
                throw new \Exception(__('This is an invalid subscription confirmation code.'));
            }
        } else {
           throw new \Exception(__('This is an invalid subscription ID.'));
        }
    }

    public function postUnsubscribe($id, $confirmCode){
        $subscriber = $this->subscriberModel->load($id);
        $subscriber->setCheckCode($confirmCode);
        $subscriber->unsubscribe();

        return $subscriber;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Newsletter\Model\ResourceModel\Subscriber\Collection
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria){

        $collection = $this->subscribersCollection;

        $this->collectionProcessor->process($searchCriteria, $collection);
        $collection->load();

        return $collection;
    }

}

