<?php
namespace Swaminathan\Offers\Model\ResourceModel;


class Offers extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	
	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init('offers_for_you', 'entity_id');
	}
	
}
