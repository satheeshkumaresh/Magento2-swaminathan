<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Swaminathan\ExternalImage\Model\Cart;

use Magento\Checkout\CustomerData\DefaultItem;
use Magento\Framework\App\ObjectManager;

/**
 * @api
 * @since 100.0.2
 */
class ImageProvider extends \Magento\Checkout\Model\Cart\ImageProvider
{
    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * @var \Magento\Checkout\CustomerData\ItemPoolInterface
     * @deprecated 100.2.7 No need for the pool as images are resolved in the default item implementation
     * @see \Magento\Checkout\CustomerData\DefaultItem::getProductForThumbnail
     */
    protected $itemPool;

    /**
     * @var \Magento\Checkout\CustomerData\DefaultItem
     * @since 100.2.7
     */
    protected $customerDataItem;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    private $imageHelper;

    /**
     * @var \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface
     */
    private $itemResolver;

    /**
     * @param \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository
     * @param \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool
     * @param DefaultItem|null $customerDataItem
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver
     */

    protected $productrepository;  
    public function __construct(
        \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository,
        \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool,
        \Magento\Checkout\CustomerData\DefaultItem $customerDataItem = null,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface $itemResolver = null,
        \Magento\Catalog\Api\ProductRepositoryInterface $productrepository,
    ) {
        parent::__construct(
        $itemRepository,
        $itemPool,
        $customerDataItem = null,
        $imageHelper,
        $itemResolver = null
        );
        $this->imageHelper = $imageHelper;
        $this->productrepository = $productrepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getImages($cartId)
    {
        $itemData = [];

        /** @see code/Magento/Catalog/Helper/Product.php */
        $items = $this->itemRepository->getList($cartId);
        /** @var \Magento\Quote\Model\Quote\Item $cartItem */
        foreach ($items as $cartItem) {
            $itemData[$cartItem->getItemId()] = $this->getProductImageData($cartItem);
        }
        return $itemData;
    }

    /**
     * Get product image data
     *
     * @param \Magento\Quote\Model\Quote\Item $cartItem
     *
     * @return array
     */
    private function getProductImageData($cartItem)
    {
        $productid = $cartItem->getData('product_id');
        $productDetail = $this->productrepository->getById($productid);
        if(($productDetail->getData('main_image_s3_url')) !== null) {
            $productImage = $productDetail->getData('main_image_s3_url');
        } else if($productDetail->getData('image') !== null) {
            $productImage = $this->imageHelper->init($productDetail, 'product_page_image_small')
                ->setImageFile($productDetail->getSmallImage()) // image,small_image,thumbnail
                ->resize(380)
                ->getUrl();
        } else {
            $productImage = ($this->imageHelper->getDefaultPlaceholderUrl('image'));
        }
        $imageData = [
            'src' => $productImage,
            'alt' => $productDetail->getData('name'),
        ];
        return $imageData;
    }
}
