<?php

namespace Swaminathan\Contact\Controller\Adminhtml\Contact;

use Swaminathan\Contact\Controller\Adminhtml\Contact;

class Index extends \Magento\Backend\App\Action {
    protected $resultPageFactory = false;
        public function __construct(
            \Magento\Backend\App\Action\Context $context,
            \Magento\Framework\View\Result\PageFactory $resultPageFactory
        )
        {
            parent::__construct($context);
            $this->resultPageFactory = $resultPageFactory;
        }
        public function execute()
        {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend((__('Contact Us')));
            return $resultPage;
        }
    }
