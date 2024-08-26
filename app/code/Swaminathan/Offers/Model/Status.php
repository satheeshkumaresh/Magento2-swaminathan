<?php
  
namespace Swaminathan\Offers\Model;

use Magento\Framework\Data\OptionSourceInterface;
use Swaminathan\HomePage\Helper\CategoryHelper;
use Psr\Log\LoggerInterface;

class Status implements OptionSourceInterface
{

    public function __construct( 
        CategoryHelper $categoryHelper,
        LoggerInterface $logger
    ) { 
        $this->categoryHelper = $categoryHelper;
        $this->logger = $logger;
    }

    /**
     * Get Grid row status type labels array.
     * @return array
     */
    public function getOptionArray()
    {
        $options = ['0' => __('Pending'),'1' => __('Resolved')];
        return $options;
    }

    /**
     * Get Grid row status type labels array.
     * @return array
     */
    public function getStatusArray()
    {
        $options = ['1' => __('Enabled'),'0' => __('Disabled')];
        return $options;
    }

    public function getLimittedArray()
    {
        $options = ['1' => __('Yes'),'0' => __('No')];
        return $options;
    }

    public function getCategories(){

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

    /**
     * Get Grid row status labels array with empty value for option element.
     *
     * @return array
     */
    public function getAllOptions()
    {
        $res = $this->getOptions();
        array_unshift($res, ['value' => '', 'label' => '']);
        return $res;
    }

    

    /**
     * Get Grid row type array for option element.
     * @return array
     */
    public function getOptions()
    {
        $res = [];
        foreach ($this->getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getOptions();
    }
}
