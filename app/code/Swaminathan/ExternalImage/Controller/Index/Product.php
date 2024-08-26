<?php
namespace Swaminathan\ExternalImage\Block;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
class Product extends \Magento\Framework\View\Element\Template
{
   protected $productrepository;  

   public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productrepository,
        TimezoneInterface $timezoneInterface
    ) {
        $this->productrepository = $productrepository;
        $this->timezoneInterface = $timezoneInterface;
   }

   public function getProductDataUsingId($productid) {
       return $this->productrepository->getById($productid);
   }

}

