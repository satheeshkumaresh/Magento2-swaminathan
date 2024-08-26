<?php

namespace Swaminathan\About\Controller\Adminhtml\Items;

class NewAction extends \Swaminathan\About\Controller\Adminhtml\Items
{

    public function execute()
    {
        $this->_forward('edit');
    }
}
