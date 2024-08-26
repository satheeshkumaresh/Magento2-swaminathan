<?php
namespace Swaminathan\LayeredFilter\Model;
use Swaminathan\LayeredFilter\Api\FilterInterface;
use Magento\Catalog\Model\Layer\Category\FilterableAttributeList;
use Magento\Catalog\Model\Layer\FilterListFactory;
use Magento\Catalog\Model\Layer\Resolver as layerResolver;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

class Filter implements FilterInterface
{
    /**
     * @var \Magento\Catalog\Model\Layer\Category\FilterableAttributeList
     */
    protected $filterableAttributes;
    /**
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    protected $layerResolver;
    /**
     * @var \Magento\Catalog\Model\Layer\FilterListFactory
     */
    protected $filterListFactory;

    /**
     * Your constructor.
     * @param \Magento\Catalog\Model\Layer\Category\FilterableAttributeList $filterableAttributes
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param FilterListFactory $filterListFactory
     */
    public function __construct(
        FilterableAttributeList $filterableAttributes,
        layerResolver $layerResolver,
        FilterListFactory $filterListFactory
    ) {
        $this->filterableAttributes = $filterableAttributes;
        $this->layerResolver = $layerResolver;
        $this->filterListFactory = $filterListFactory;
    }

    /**
     * Get filters for a category page.
     * @param int $category_id
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function retrieve($category_id): array
    {
        $returnFilters = [];
        $filterableAttributes = $this->filterableAttributes;
        $layerResolver = $this->layerResolver;
        $filterList = $this->filterListFactory->create(['filterableAttributes' => $filterableAttributes]);
        $layer = $layerResolver->get();
        $layer->setCurrentCategory($category_id);
        $layer->getProductCollection()
            ->addAttributeToFilter("visibility", ["neq" => Visibility::VISIBILITY_NOT_VISIBLE])
            ->addAttributeToFilter("status", ["eq" => Status::STATUS_ENABLED]);
        foreach ($filterList->getFilters($layer) as $filter) {
            $values = [];
            foreach ($filter->getItems() as $item) {
                $values[] = [
                    "display" => strip_tags($item->getLabel()), 
                    "value" => $item->getValue(),
                    "count" => $item->getCount()
                ];
            }
            if (!empty($values)) {
                $returnFilters[] = [
                    "attr_code" => $filter->getRequestVar(),
                    "attr_label" => $filter->getName(),
                    "values" => $values
                ];
            }
        }
        return $returnFilters;
    }
}