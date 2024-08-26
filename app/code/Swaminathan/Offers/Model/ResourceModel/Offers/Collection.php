<?php
namespace Swaminathan\Offers\Model\ResourceModel\Offers;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'entity_id';
	protected $_eventPrefix = 'offers_for_you';
	protected $_eventObject = 'offer_for_you';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Swaminathan\Offers\Model\Offers', 'Swaminathan\Offers\Model\ResourceModel\Offers');
	}

}

