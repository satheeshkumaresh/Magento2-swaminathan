<?php
/**
  
 */
namespace Swaminathan\HomePage\Block\System\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Checkbox extends \Magento\Config\Block\System\Config\Form\Field
{
    const CONFIG_PATH = 'hompage/tile4/open_in_new';

    protected $_template = 'Swaminathan_HomePage::system/config/checkbox.phtml';

    protected $_values = null;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ScopeConfigInterface $scopeConfig,

        
        array $data = []
    ) {
        $this->scopeConfig= $scopeConfig;

        parent::__construct($context, $data);
    }
    /**
     * Retrieve element HTML markup.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());

        return $this->_toHtml();
    }
    
    public function getValues()
    {
        $values = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        foreach ($objectManager->create('Swaminathan\HomePage\Model\Config\Source\Checkbox')->toOptionArray() as $value) {
            $values[$value['value']] = $value['label'];
        }

        return $values;
    }
    /**
     * 
     * @param  $name 
     * @return boolean
     */
    public function getIsChecked()
    {
        return$this->scopeConfig->getValue(self::CONFIG_PATH,
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
         );
    }
    /**
     * 
     *get the checked value from config
     */
    public function getCheckedValues()
    {
        if (is_null($this->_values)) {
            $data = $this->getConfigData();
            if (isset($data[self::CONFIG_PATH])) {
                $data = $data[self::CONFIG_PATH];
            } else {
                $data = '';
            }
            $this->_values = explode(',', $data);
        }
        

        return $this->_values;
    }
}