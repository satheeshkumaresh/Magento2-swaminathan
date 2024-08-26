<?php
 
namespace Swaminathan\SparshBanner\Controller\Adminhtml\Banner;

use Sparsh\Banner\Model\Banner;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\MediaStorage\Model\File\UploaderFactory as MobileUploadFactory;
// use Magento\Framework\File\Uploader as MobileUploadFactory;

/**
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Save extends \Sparsh\Banner\Controller\Adminhtml\Banner\Save
{
    /**
     * FileSystem
     *
     * @var \Magento\Framework\Filesystem
     */
    public $filesystem;

    /**
     * UploaderFactory
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    public $uploaderfactory;
    public $mobileuploaderfactory;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context           $context         context
     * @param Filesystem                                    $fileSystem      fileSystem
     * @param UploaderFactory                               $uploaderfactory uploaderfactory
     * @param \Sparsh\Banner\Model\BannerFactory        $bannerFactory   bannerFactory
     * @param \Sparsh\Banner\Model\ResourceModel\Banner $bannerResource  bannerResource
     */
    
    public function __construct(
        MobileUploadFactory $mobileuploaderfactory,
        \Magento\Backend\App\Action\Context $context,
        Filesystem $fileSystem,
        UploaderFactory $uploaderfactory,
        \Sparsh\Banner\Model\BannerFactory $bannerFactory,
        \Sparsh\Banner\Model\ResourceModel\Banner $bannerResource,
        \Sparsh\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerCollectionFactory,
        ) {
        // $this->filesystem = $fileSystem;
        // $this->uploaderfactory = $uploaderfactory;
        $this->mobileuploaderfactory = $mobileuploaderfactory;
        parent::__construct($context, $fileSystem, $uploaderfactory, $bannerFactory, $bannerResource, $bannerCollectionFactory);
    }

    /**
     * Before save method
     *
     * @param \Sparsh\Banner\Model\Banner         $model   model
     * @param \Magento\Framework\App\RequestInterface $request request
     *
     * @return bool|void
     */
    protected function _beforeSave($model, $request)
    {
        $data = $model->getData();
        $model->setData($data);
        $mediaDirectory = $this->filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        );
        
        if ($data['banner_type'] == "Image") {
            // Desktop banner image
            $imageField = 'banner_image';
            // Mobile banner image
            $mobileImageField = 'banner_image_mobile';
            $uploader =$this->uploaderfactory;
            $mobileup =$this->mobileuploaderfactory;
            /* prepare banner image */
            if (isset($data[$imageField]) && isset($data[$imageField]['value'])) {
                if (isset($data[$imageField]['delete'])) {
                    unlink($mediaDirectory->getAbsolutePath() . $data[$imageField]['value']);
                    $model->setData($imageField, '');
                } else {
                    $model->setData($imageField, $data[$imageField]['value']);
                }
            }
            try {
                $uploader = $uploader->create(['fileId' => 'post['.$imageField.']']);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'webp']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->setAllowCreateFolders(true);
                $result = $uploader->save(
                    $mediaDirectory->getAbsolutePath(Banner::BASE_IMAGE_MEDIA_PATH)
                );
                $model->setData(
                    $imageField,
                    Banner::BASE_IMAGE_MEDIA_PATH . $result['file']
                );
            } catch (\Exception $e) {
                if ($e->getCode() != \Magento\Framework\File\Uploader::TMP_NAME_EMPTY) {
                    $this->messageManager->addExceptionMessage(
                        $e,
                        __('Please Insert Image of types jpg, jpeg, gif, png, webp')
                    );
                }
            }
            if (isset($data[$mobileImageField]) && isset($data[$mobileImageField]['value'])) {
                if (isset($data[$mobileImageField]['delete'])) {
                    unlink($mediaDirectory->getAbsolutePath() . $data[$mobileImageField]['value']);
                    $model->setData($mobileImageField, '');
                } else {
                    $model->setData($mobileImageField, $data[$mobileImageField]['value']);
                }
            }
            try {
                $mobileup = $mobileup->create(['fileId' => 'post['.$mobileImageField.']']);
                $mobileup->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'webp']);
                $mobileup->setAllowRenameFiles(true);
                $mobileup->setFilesDispersion(true);
                $mobileup->setAllowCreateFolders(true);
                $mobileResult = $mobileup->save(
                    $mediaDirectory->getAbsolutePath(Banner::BASE_IMAGE_MEDIA_PATH)
                );
                $model->setData(
                    $mobileImageField,
                    Banner::BASE_IMAGE_MEDIA_PATH . $mobileResult['file']
                );
            
            } catch (\Exception $e) {
                if ($e->getCode() != \Magento\Framework\File\Uploader::TMP_NAME_EMPTY) {
                    $this->messageManager->addExceptionMessage(
                        $e,
                        __('Please Insert Image of types jpg, jpeg, gif, png, webp')
                    );
                }
            }
        } else {
            $model->setData('banner_image', '')
                ->setData('banner_image_mobile', '')
                ->setData('banner_title', '')
                ->setData('label_button_text', '')
                ->setData('call_to_action', '')
                ->setData('banner_description', '');
        }
 
    }
}
