<?php

namespace Swaminathan\HomePage\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class CurrencyHelper extends AbstractHelper
{
    public function __construct( 
        PriceCurrencyInterface $priceCurrency
    ) { 
        $this->priceCurrency = $priceCurrency;
    }
     
    public function getCurrentCurrencySymbol()
    {
        return $this->priceCurrency->getCurrencySymbol();
    }
}