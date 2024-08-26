<?php
namespace Swaminathan\Offers\Model;
class Offers extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'offers_for_you';

	protected $_cacheTag = 'offers_for_you';

	protected $_eventPrefix = 'offers_for_you';

	protected function _construct()
	{
		$this->_init('Swaminathan\Offers\Model\ResourceModel\Offers');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getEntityId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
}
