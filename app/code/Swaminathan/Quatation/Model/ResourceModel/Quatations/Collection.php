<?php

namespace Swaminathan\Quatation\Model\ResourceModel\Quatations;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     * @codingStandardsIgnoreStart
     */
    protected $_idFieldName = 'id';

    /**
     * Collection initialisation
     */
    protected function _construct()
    {
        // @codingStandardsIgnoreEnd
        $this->_init('Swaminathan\Quatation\Model\Quatations', 'Swaminathan\Quatation\Model\ResourceModel\Quatations');
    }
}


