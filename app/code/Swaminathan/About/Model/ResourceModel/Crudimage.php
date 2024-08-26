<?php

namespace Swaminathan\About\Model\ResourceModel;

class Crudimage extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('swaminathan_aboutus', 'entity_id');   
    }
}