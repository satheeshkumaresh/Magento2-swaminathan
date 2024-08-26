<?php

namespace Swaminathan\Contact\Model;

use Magento\Framework\Model\AbstractModel;

class Contact extends AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'swaminathan_contactus';

    /**
     * Initialise resource model
     * @codingStandardsIgnoreStart
     */
    protected function _construct()
    {
        // @codingStandardsIgnoreEnd
        $this->_init('Swaminathan\Contact\Model\ResourceModel\Contact');
    }

    /**
     * Get cache identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

} 