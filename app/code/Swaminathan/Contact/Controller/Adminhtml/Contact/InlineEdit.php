<?php
declare(strict_types=1);

namespace Swaminathan\Contact\Controller\Adminhtml\Contact; 

class InlineEdit extends \Magento\Backend\App\Action
{

    protected $jsonFactory;
    
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Swaminathan\Contact\Model\ContactFactory $contactFactory,
        \Psr\Log\LoggerInterface $logger
       
        
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->contactFactory = $contactFactory;  
        $this->logger = $logger;
    
    }

    /**
     * Inline edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];
        
        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);    
        $this->logger->debug('PostItem:----->'.print_r($postItems,true) );   
            if (empty($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $entityId) {
                   try{
                    $postData = $this->contactFactory->create();
                    $postData->load($entityId);
                    $postData->setData(array_merge($postData->getData(), $postItems[$entityId]))->save();
                   }
                   catch (\Exception $e) {
                        $messages[] = "[Error:]  {$e->getMessage()}";
                        $error = true;
                    }
                }
            }

        }
        
        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}

