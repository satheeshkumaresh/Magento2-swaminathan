<?php

namespace Swaminathan\Contact\Model\Contact\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('Open')],
            ['value' => '0', 'label' => __('Closed')]
        ];
    }
}
