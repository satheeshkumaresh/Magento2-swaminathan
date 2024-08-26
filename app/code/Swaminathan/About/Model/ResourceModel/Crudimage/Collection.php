<?php

namespace Swaminathan\About\Model\ResourceModel\Crudimage;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Swaminathan\About\Model\Crudimage',
            'Swaminathan\About\Model\ResourceModel\Crudimage'
        );
    }
}