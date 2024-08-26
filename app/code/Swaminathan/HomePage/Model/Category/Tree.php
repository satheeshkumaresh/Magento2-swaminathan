<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Swaminathan\HomePage\Model\Category;

use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\Catalog\Api\Data\CategoryTreeInterfaceFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use  Magento\Catalog\Model\CategoryRepository;
/**
 * Retrieve category data represented in tree structure
 */
class Tree extends \Magento\Catalog\Model\Category\Tree
{
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree,
        StoreManagerInterface $storeManager,
        Collection $categoryCollection,
        CategoryTreeInterfaceFactory $treeFactory,
        CategoryRepository $categoryRepository,
        UrlHelper $urlHelper,
        TreeFactory $treeResourceFactory = null
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->urlHelper = $urlHelper;
        parent::__construct(
            $categoryTree,
            $storeManager,
            $categoryCollection,
            $treeFactory,
            $treeResourceFactory,
        );
    }
    
    public function getTree($node, $depth = null, $currentLevel = 0)
    {
        /** @var CategoryTreeInterface[] $children */
        $children = $this->getChildren($node, $depth, $currentLevel);
        /** @var CategoryTreeInterface $tree */
        $tree = $this->treeFactory->create();
        $tree->setId($node->getId())
            ->setParentId($node->getParentId())
            ->setName($node->getName())
            ->setPosition($node->getPosition())
            ->setLevel($node->getLevel())
            ->setIsActive($node->getIsActive())
            ->setProductCount($node->getProductCount())
            ->setChildrenData($children);
        $categories[] = $tree->toArray();
         
        foreach($categories as $category){
            $categoryId = $category['entity_id'];
            $urlKey = $this->getUrlKeyByCategoryId($categoryId);
            $category['urlKey'] = $urlKey;
            $finalCategroy[] = $category;
        }
        return $finalCategroy;
    }

    /**
     * Get node children.
     *
     * @param Node $node
     * @param int $depth
     * @param int $currentLevel
     * @return CategoryTreeInterface[]|[]
     */
    protected function getChildren($node, $depth, $currentLevel)
    {
        if ($node->hasChildren()) {
            $children = [];
            foreach ($node->getChildren() as $child) {
                if ($depth !== null && $depth <= $currentLevel) {
                    break;
                }
                $children[] = $this->getTree($child, $depth, $currentLevel + 1);
            }
            return $children;
        }
        return [];
    }

    public function getUrlKeyByCategoryId($categoryId)
    {
        $category = $this->categoryRepository->get($categoryId, $this->storeManager->getStore()->getId());
        $url = $category->getUrl();
        $baseUrl = $this->urlHelper->getBaseUrl();
        $reactUrl = ($this->urlHelper->getReactUrl()) ? $this->urlHelper->getReactUrl() : '';
        return str_replace($baseUrl,$reactUrl,$url);
    }
}
