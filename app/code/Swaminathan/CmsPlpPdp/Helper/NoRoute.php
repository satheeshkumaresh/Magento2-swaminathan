<?php
namespace Swaminathan\CmsPlpPdp\Helper;

use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\Cms\Model\PageRepository;
class NoRoute
{

    protected $urlFinderInterface;
    protected $pageRepository;
    public function __construct(
        UrlFinderInterface $urlFinderInterface,
        PageRepository $pageRepository
    )
    {
        $this->urlFinderInterface = $urlFinderInterface;
        $this->pageRepository = $pageRepository;
    }
    public function getNoRoute(){
        $urlKey = ['request_path' => 'no-route'];
        $rewrite = $this->urlFinderInterface->findOneByData($urlKey);
        $type = 'cms-page';
        $entityId =  $rewrite->getEntityId();
        $data = [
            'entityType' => $type,
            'entityId' => $entityId,
            'urlKey' => $rewrite->getRequestPath() 
        ];
        // get the details of cms page
        $data['content'] = $this->pageRepository->getById($entityId)->getData();
        $response [] = $data;
        $responseData[] = [
            "code" => 200,
            "status" => true,
            "data" => $response
        ];
        return $responseData;
    }
}
