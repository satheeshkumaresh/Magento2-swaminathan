<?php

namespace Swaminathan\Quatation\Model;

use Magento\Framework\Model\AbstractModel;

class Quatations extends AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'swaminathan_quatation';

    /**
     * Initialise resource model
     * @codingStandardsIgnoreStart
     */
    protected function _construct()
    {
        // @codingStandardsIgnoreEnd
        $this->_init('Swaminathan\Quatation\Model\ResourceModel\Quatations');
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