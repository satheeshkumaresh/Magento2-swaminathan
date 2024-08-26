<?php
namespace Swaminathan\HomePage\Model\Config\Source;
use Swaminathan\HomePage\Helper\CategoryHelper;
use Psr\Log\LoggerInterface;
class ListMode implements \Magento\Framework\Data\OptionSourceInterface
{
    public function __construct( 
        CategoryHelper $categoryHelper,
        LoggerInterface $logger
    ) { 
        $this->categoryHelper = $categoryHelper;
        $this->logger = $logger;
    }
    public function toOptionArray()
    {
        $categories = $this->categoryHelper->getCategories();
        
        if(count($categories)){

            foreach($categories as $category){
                $id = $category['id'];
                $name = $category['name'];
                $level = $category['level'];
                $prefix= '&#8227; ';
                $space = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                if($level ==  2){
                    $space = '';
                }
                elseif($level ==  3){
                    $space = $space;
                }
                elseif($level == 4){
                    $space = $space.$space;
                }
                $name = $space.$prefix.$name;
    
                $finalCategories[] = ['value' => $id , 'label' => $name ];
            }
            
            return $finalCategories;
        }
    }
}