<?php

namespace Swaminathan\Sms\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Msgtype
 * @package Swaminathan\Sms\Model\Config\Source
 */
class Msgtype implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '4', 'label' => __('Transactional')],
            ['value' => '1', 'label' => __('Promotional')]
        ];
    }
}
