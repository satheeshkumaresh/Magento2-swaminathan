<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Swaminathan\Cart\Model\GuestCart;

use Magento\Quote\Api\Data\CartItemInterface;
use Swaminathan\Cart\Api\CartItemRepositoryInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Swaminathan\Cart\Model\DeleteAllCart;
/**
 * Cart Item repository class for guest carts.
 */
class GuestCartItemRepository implements \Magento\Quote\Api\GuestCartItemRepositoryInterface
{
    /**
     * @var \Swaminathan\Cart\Api\CartItemRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Swaminathan\Cart\Model\DeleteAllCart
     */
    protected $deleteAllCart;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * Constructs a read service object.
     *
     * @param \Swaminathan\Cart\Api\CartItemRepositoryInterface $repository
     * @param \Swaminathan\Cart\Model\DeleteAllCart $deleteAllCart
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        \Swaminathan\Cart\Api\CartItemRepositoryInterface $repository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        DeleteAllCart $deleteAllCart
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->repository = $repository;
        $this->deleteAllCart = $deleteAllCart;
    }

    /**
     * {@inheritdoc}
     */
    public function getList($cartId)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $cartItemList = $this->repository->getList($quoteIdMask->getQuoteId());
        /** @var $item CartItemInterface */
        foreach ($cartItemList as $item) {
            $item->setQuoteId($quoteIdMask->getMaskedId());
        }
        return $cartItemList;
    }

    /**
     * {@inheritdoc}
     */
    public function save(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartItem->getQuoteId(), 'masked_id');
        $cartItem->setQuoteId($quoteIdMask->getQuoteId());
        return $this->repository->addToCart($cartItem);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($cartId, $itemId)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->repository->deleteById($quoteIdMask->getQuoteId(), $itemId);
    }
    // Delete all cart items by cart id
    public function deleteAllCartItems($cartId)
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->deleteAllCart->deleteAllCart($quoteIdMask->getQuoteId());
    }
}
