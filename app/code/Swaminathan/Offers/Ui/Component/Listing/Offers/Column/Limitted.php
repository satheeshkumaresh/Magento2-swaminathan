<?php

namespace Swaminathan\Offers\Ui\Component\Listing\Offers\Column;

class Limitted implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Unlimitted')],
            ['value' => 1, 'label' => __('Limtted')]
        ];
    }
}