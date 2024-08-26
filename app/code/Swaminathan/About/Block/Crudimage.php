<?php

namespace Swaminathan\About\Block;

/**
 * Crudimage content block
 */
class Crudimage extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        parent::__construct($context);
    }

    public function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('About Us'));
        
        return parent::_prepareLayout();
    }
}
