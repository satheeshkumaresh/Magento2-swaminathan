<?php

namespace Swaminathan\About\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Item Visibility functionality model
 */
class Visibility extends AbstractSource implements SourceInterface, OptionSourceInterface
{
    /**#@+
     * Item Visibility values
     */
    const PRODUCT_FOR_MANUFACTURING = 0;

    const WHOLESALE_DEALERS = 1;

    /**#@-*/

    /**
     * Retrieve option array
     *
     * @return string[]
     */
    public static function getOptionArray()
    {
        return [self::PRODUCT_FOR_MANUFACTURING => __('Products for Manufacturing'), self::WHOLESALE_DEALERS => __('Wholesale Dealers')];
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function getAllOptions()
    {
        $result = [];

        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }
}