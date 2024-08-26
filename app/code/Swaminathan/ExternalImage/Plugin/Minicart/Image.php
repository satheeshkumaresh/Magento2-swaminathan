<?php

namespace Swaminathan\ExternalImage\Plugin\Minicart;
use Magento\Checkout\CustomerData\AbstractItem;

class Image extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Product $productDetails,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Helper\Image $imageHelpher,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productDetails = $productDetails;
        $this->productRepository = $productRepository;
        $this->imageHelpher = $imageHelpher;
    }

    public function afterGetItemData(AbstractItem $item, $result)
    {
        if ($result['product_id'] > 0) {
            $product = $this->productRepository->getById($result['product_id']);
            if($product->getData('main_image_s3_url') !== null) {
                $result['product_image']['src'] = $product->getData('main_image_s3_url');
            } else {
                return $result;
            }
            return $result;
        }
        return $result;
    }
}