<?php

namespace Swaminathan\About\Controller\Adminhtml\Items;

class Index extends \Swaminathan\About\Controller\Adminhtml\Items
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Swaminathan_About::about_us');
        $resultPage->getConfig()->getTitle()->prepend(__('About Us'));
        $resultPage->addBreadcrumb(__('About Us'), __('About Us'));
        $resultPage->addBreadcrumb(__('About Us'), __('About Us'));
        return $resultPage;
    }
}