<?php
namespace Swaminathan\ProductAlert\Api;

use Exception;

/**
 * Interface ProductAlertManagementInterface
 * @api
 */
interface ProductAlertManagementInterface
{
    /**
     * Return true if product Added to Alert.
     *
     * @param string $customerEmail
     * @param int $productId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addProductAlertStock($customerEmail, $productId);
}
