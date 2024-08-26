<?php

namespace Swaminathan\About\Block;

use Magento\Framework\View\Element\Template\Context;
use Swaminathan\About\Model\CrudimageFactory;
use Magento\Cms\Model\Template\FilterProvider;
/**
 * Crudimage View block
 */
class CrudimageView extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Crudimage
     */
    protected $_crudimage;
    public function __construct(
        Context $context,
        CrudimageFactory $crudimage,
        FilterProvider $filterProvider
    ) {
        $this->_crudimage = $crudimage;
        $this->_filterProvider = $filterProvider;
        parent::__construct($context);
    }

    public function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('About Us Module View Page'));
        
        return parent::_prepareLayout();
    }

    public function getSingleData()
    {
        $id = $this->getRequest()->getParam('id');
        $crudimage = $this->_crudimage->create();
        $singleData = $crudimage->load($id);
        if($singleData->getCrudimageId() && $singleData->getStatus() == 1){
            return $singleData;
        }else{
            return false;
        }
    }
}