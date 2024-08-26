<?php

namespace Swaminathan\About\Model;

use Magento\Framework\Model\AbstractModel;

class Crudimage extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Swaminathan\About\Model\ResourceModel\Crudimage');
    }
}