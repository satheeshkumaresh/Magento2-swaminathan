<?php

namespace Swaminathan\Contact\Model\ResourceModel\Contact;

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
        $this->_init('Swaminathan\Contact\Model\Contact', 'Swaminathan\Contact\Model\ResourceModel\Contact');
    }
}


