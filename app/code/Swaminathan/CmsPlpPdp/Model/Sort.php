<?php
namespace Swaminathan\CmsPlpPdp\Model;

use Psr\Log\LoggerInterface;

class Sort 
{
    const RELEVENCE = 0;
    const NEW_ARRIVAL = 7;
    const PRICE_HIGH_TO_LOW = 1;
    const PRICE_LOW_TO_HIGH = 2;
    const NAME_ASC = 3;
    const NAME_DESC = 4;
    const POSITION_ASC = 5;
    const POSITION_DESC = 6;
    public function __construct( 
        LoggerInterface $logger
    ) { 
        $this->logger = $logger;
    }

    /**
     * Get Grid row sort option labels array.
     * @return array
     */
    public function getOptionArray()
    {
        $options = [
                        self::RELEVENCE => __('Relevance'),
                        self::NEW_ARRIVAL => __('New Arrival'),
                        self::PRICE_HIGH_TO_LOW => __('Price : High to Low'),
                        self::PRICE_LOW_TO_HIGH => __('Price : Low to High'),
                        self::NAME_ASC => __('Name : A to Z'),
                        self::NAME_DESC => __('Name : Z to A')
                    ];
        return $options;
    }
    public function getOptions()
    {
        $res = [];
        foreach ($this->getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }
}