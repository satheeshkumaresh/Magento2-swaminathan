<?php

namespace Swaminathan\HomePage\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Api\Data\CategoryTreeInterface;
use Magento\Catalog\Api\CategoryManagementInterface;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
class CategoryHelper extends AbstractHelper
{
    public function __construct( 
        StoreManagerInterface $storeManager,
        CollectionFactory $categoryCollection,
        Category $category,
        CategoryManagementInterface $categoryManagement,
        UrlHelper $urlHelper,
        UrlRewriteFactory $urlRewriteFactory,
        CategoryRepository $categoryRepository
    ) { 
        $this->storeManager = $storeManager;
        $this->categoryCollection =  $categoryCollection;
        $this->category =  $category;
        $this->categoryManagement = $categoryManagement;
        $this->urlHelper = $urlHelper;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->categoryRepository = $categoryRepository;
    }
    public function getCategories(){
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $parentId = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $children =    $this->categoryCollection->create()     
            ->setStore($this->storeManager->getStore())
            ->addFieldToFilter('parent_id',$parentId);
        if(count($children) > 0){
            $i=0;
            foreach ($children as $category) {
                $id = $category->getEntityId();
                if($id){
                $name = $this->getCategoryNameById($category->getEntityId());
                $data[$i]['id'] = $id;
                $data[$i]['name'] = $name;
                $data[$i]['level'] = $category->getLevel();
                $data[$i]['subcategory'] = $this->mycategory($id);
                $i++;
                }
            }
        }
        $categoryTree = [];
        foreach($data as $d){
            $id =$d['id'];
            $name = $d['name'];
            $level = $d['level'];
            array_push($categoryTree,['id'=>$id,'name'=>$name,'level'=>$level]);
            if($d['subcategory']){
                foreach($d['subcategory'] as $sub){
                    $id =$sub['id'];
                    $name = $sub['name'];
                    $level = $sub['level'];
                    array_push($categoryTree,['id'=>$id,'name'=>$name,'level'=>$level]);
                    if($sub['subcategory']){
                        foreach($sub['subcategory'] as $subd){
                            $id =$subd['id'];
                            $name = $subd['name'];
                            $level = $subd['level'];
                            array_push($categoryTree,['id'=>$id,'name'=>$name,'level'=>$level]);
                        }
                    }
                }
            }
        }
        return $categoryTree;
    }
    public function mycategory($id){
        $children = $this->categoryCollection->create()     
            ->setStore($this->storeManager->getStore())
            ->addFieldToFilter('parent_id',$id)
            ->addFieldToFilter('is_active', 1)  
            ->addFieldToFilter('include_in_menu', 1);
        if(count($children) > 0){
            $i=0;
            foreach ($children as $category) {
                $id = $category->getId();
                $name = $this->getCategoryNameById($category->getEntityId());
                $sub[$i]['id'] = $category->getEntityId();
                $sub[$i]['name'] = $name;
                $sub[$i]['level'] = $category->getLevel();
                $sub[$i]['subcategory'] = $this->mycategory($id);
                $i++;
            }
        }
        if(isset($sub)){

            return $sub;
        }
    }

    public function getCategoryNameById($categoryId){
        $category = $this->category->load($categoryId);
        return $category->getName();
    }

    public function getCategoryUrlKeyById($categoryId){   
        $category = $this->categoryCollection->create()
                        ->addFieldToFilter('entity_id',$categoryId);
        $categoryData = $category->getData();
        if(!empty($categoryData)){
            $category = $this->categoryRepository->get($categoryId);
            $url = $category->getUrl();
        }
        else{
            $url = "";
        }
        $baseUrl = $this->urlHelper->getBaseUrl();
        return $url && $baseUrl ? str_replace($baseUrl,'',$url) : $url;
    }

    public function getProductUrlKey($productUrl){
        $baseUrl = $this->urlHelper->getBaseUrl();
        return $productUrl && $baseUrl ? str_replace($baseUrl, '', $productUrl) : $productUrl;
    }
}