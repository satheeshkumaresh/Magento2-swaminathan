<?php
namespace Swaminathan\Offers\Controller\Adminhtml\Offers;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class DeleteRow extends Action
{
    public $gridFactory;
    
    
    public function __construct(
        Context $context,        
        \Swaminathan\Offers\Model\OffersFactory $gridFactory , 
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory 

    ) {
        $this->gridFactory = $gridFactory;
        $this->resultRedirectFactory =  $resultRedirectFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $rowId = (int) $this->getRequest()->getParam('id');
        try {
            $rowData = $this->gridFactory->create();
            $rowData = $rowData->load($rowId);
            $rowData->delete();
            $this->messageManager->addSuccessMessage(__('Successfully Deleted the Offer.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('*/*/');
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Swamianthan_Offers::deleterow');
    }
}