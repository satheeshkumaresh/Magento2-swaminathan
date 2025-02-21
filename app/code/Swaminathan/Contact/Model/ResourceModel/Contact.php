<?php
namespace Swaminathan\Contact\Model\ResourceModel;


class Contact extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	
	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init('swaminathan_contactus', 'id');
	}
	
}