<?php

namespace Swaminathan\Reorder\Api;

use Magento\Quote\Model\Quote;

interface ReorderRepositoryInterface
{
    /**
    * Return Create reorder.
    *
    * @param int $customerId
    * @param int $orderId
    * @return Swaminathan\Reorder\Api\ReorderRepositoryInterface
    */

   public function reorderItem($customerId, $orderId);

   /**
     * Add product to shopping cart (quote)
     *
     * @param int|\Magento\Catalog\Model\Product $productInfo
     * @param array|float|int|\Magento\Framework\DataObject|null $requestInfo
     * @return $this
     */
    public function addProduct($productInfo, $requestInfo = null);

    /**
     * Save cart
     *
     * @return $this
     * @abstract
     */
    public function saveQuote();

    /**
     * Associate quote with the cart
     *
     * @param Quote $quote
     * @return $this
     * @abstract
     */
    public function setQuote(Quote $quote);

    /**
     * Get quote object associated with cart
     *
     * @return Quote
     * @abstract
     */
    public function getQuote();
}