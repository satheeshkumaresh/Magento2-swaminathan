<?php

namespace Swaminathan\Sms\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Apiprovider
 * @package Swaminathan\Sms\Model\Config\Source
 */
class Apiprovider implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'msg', 'label' => __('MSG 91')],
            ['value' => 'twilio', 'label' => __('Twilio')],
            ['value' => 'bulksms', 'label' => __('BulkSMS')],
            ['value' => 'other', 'label' => __('Other')]
        ];
    }
}
