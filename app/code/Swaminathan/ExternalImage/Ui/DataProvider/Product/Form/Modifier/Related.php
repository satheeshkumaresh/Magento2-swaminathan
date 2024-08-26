<?php

namespace Swaminathan\ExternalImage\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductLinkInterface;
use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class Related extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Related
{
    protected function fillData(ProductInterface $linkedProduct, ProductLinkInterface $linkItem)
    {
        if($linkedProduct->getData('main_image_s3_url') !== null) {
            $imageUrl = $linkedProduct->getData('main_image_s3_url');
        } else if(($this->imageHelper->init($linkedProduct, 'product_listing_thumbnail')->getUrl()) !== null) {
            $imageUrl = $this->imageHelper->init($linkedProduct, 'product_listing_thumbnail')->getUrl();
        } else {
            $imageUrl = $this->imageHelper->getDefaultPlaceholderUrl('image');
        }

        return [
            'id' => $linkedProduct->getId(),
            'thumbnail' => $imageUrl,
            'name' => $linkedProduct->getName(),
            'status' => $this->status->getOptionText($linkedProduct->getStatus()),
            'attribute_set' => $this->attributeSetRepository
                ->get($linkedProduct->getAttributeSetId())
                ->getAttributeSetName(),
            'sku' => $linkItem->getLinkedProductSku(),
            'price' => $linkedProduct->getPrice(),
            'position' => $linkItem->getPosition(),
        ];
    }
}