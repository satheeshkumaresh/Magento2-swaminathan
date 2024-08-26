<?php
namespace Swaminathan\CmsPlpPdp\Controller\Adminhtml\Category;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Swaminathan\HomePage\Helper\ConstantsHelper;

class Delete extends \Magento\Catalog\Controller\Adminhtml\Category implements HttpPostActionInterface 
{
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Swaminathan\Offers\Model\ResourceModel\Offers\CollectionFactory $offerCollectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Swaminathan\Offers\Model\ResourceModel\Offers\CollectionFactory $offerCollectionFactory
    ) {
        parent::__construct($context);
        $this->categoryRepository = $categoryRepository;
        $this->scopeConfig= $scopeConfig;
        $this->offerCollectionFactory = $offerCollectionFactory;
    }

    //get the system config value by the constant
    public function getConfigValue($config){
        return $this->scopeConfig->getValue($config,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,    ); 
    }

    /**
     * Delete category action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $categoryId = (int)$this->getRequest()->getParam('id');
        $tileCategory1 = $this->getConfigValue(ConstantsHelper::TILE1_CATEGORY);
        $tileCategory2 = $this->getConfigValue(ConstantsHelper::TILE5_CATEGORY);
        $tileCategory3 = $this->getConfigValue(ConstantsHelper::TILE6_CATEGORY);
        // Offer Collectio Factory
        $offerCollections = $this->offerCollectionFactory->create();
        $offerCollections->addFieldToFilter('category', $categoryId);
        $offerCollectionDatas = $offerCollections->getData();
        $offerDataCount = count($offerCollectionDatas);
        foreach($offerCollectionDatas as $offerCollectionData){
            $offerCategoryIds = $offerCollectionData['category'];
        }
        $parentId = null;
        if ($categoryId) {
            try {
                if($tileCategory1 != $categoryId && $tileCategory2 != $categoryId && $tileCategory3 != $categoryId){
                    $category = $this->categoryRepository->get($categoryId);
                    $parentId = $category->getParentId();
                    $this->_eventManager->dispatch('catalog_controller_category_delete', ['category' => $category]);
                    $this->_auth->getAuthStorage()->setDeletedPath($category->getPath());
                    $this->categoryRepository->delete($category);
                    $this->messageManager->addSuccessMessage(__('You deleted the category.'));
                }
                else{
                    $this->messageManager->addErrorMessage("Couldn't able to delete this category. Because, this category is assigned in home page");
                }

            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('catalog/*/edit', ['_current' => true]);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while trying to delete the category.'));
                return $resultRedirect->setPath('catalog/*/edit', ['_current' => true]);
            }
        }
        return $resultRedirect->setPath('catalog/*/', ['_current' => true, 'id' => $parentId]);
    }
}
