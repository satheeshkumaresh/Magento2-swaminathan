<?php
 
namespace Swaminathan\Offers\Controller\Adminhtml\Offers;

use Magento\Framework\Filesystem;

class Save extends \Magento\Backend\App\Action
{
     
    var $gridFactory;

     
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Swaminathan\Offers\Model\OffersFactory $gridFactory,
        Filesystem $filesystem, 
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderfactory
    ) {
        $this->gridFactory = $gridFactory;
        $this->uploaderfactory = $uploaderfactory;
        $this->filesystem = $filesystem;
        parent::__construct($context);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $mediaDirectory = $this->filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        );
        if (!$data) {
            $this->_redirect('gridexample/gridexample/addrow');
            return;
        }
        try {
            $profileImage = $this->getRequest()->getFiles('offer_image');
          
            $fileName = ($profileImage && array_key_exists('name', $profileImage)) ? $profileImage['name'] : null;
            
            if ($profileImage && $fileName) {
                try {
                    $uploader =$this->uploaderfactory;
                    $uploader = $uploader->create(['fileId' => 'offer_image']);
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png','webp']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(true);
                    $uploader->setAllowCreateFolders(true);
                    $result = $uploader->save(
                        $mediaDirectory
                            ->getAbsolutePath('Offers')
                    );
                    $data['image'] = 'Offers'. $result['file'];
                } catch (\Exception $e) {
                    if ($e->getCode() == 0) {
                        $this->messageManager->addError($e->getMessage());
                    }
                }

            }
            else{
                if(isset($data['offer_image']['delete']) && $data['offer_image']['delete'] == 1){
                    $link = $mediaDirectory->getAbsolutePath() . $data['offer_image']['value'];
                    unlink($link);
                    $data['image'] = '';
                }
            }
            $rowData = $this->gridFactory->create();
            $rowData->setData($data);
            if (isset($data['entity_id'])) {
                $rowData->setEntityId($data['entity_id']);
            }
            $rowData->save();
            $this->messageManager->addSuccess(__('Row data has been successfully saved.'));

        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect('swaminathan_offers/offers/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Swaminathan_Offers::save');
    }
}