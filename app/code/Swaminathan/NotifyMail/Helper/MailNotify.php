<?php

namespace Swaminathan\NotifyMail\Helper;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Swaminathan\NotifyMail\Model\Mail\Template\TransportBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Utility\ChildrenClassesSearch\E;

/**
 * Class MailNotify
 */
class MailNotify extends AbstractHelper
{
    /**
     * @var HttpContext
     */
    protected $_httpContext;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Data constructor.
     * @param Context $context
     * @param HttpContext $httpContext
     * @param TransportBuilder $transportBuilder
     * @param CustomerViewHelper $customerViewHelper
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        Context $context,
        HttpContext $httpContext,
        TransportBuilder $transportBuilder,
        CustomerViewHelper $customerViewHelper,
        CustomerRegistry $customerRegistry,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->_httpContext = $httpContext;
        $this->transportBuilder = $transportBuilder;
        $this->customerViewHelper = $customerViewHelper;
        $this->customerRegistry = $customerRegistry;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

   

    /**
     * @param $path
     * @return mixed
     */
    public function getModuleConfig($path) {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->_scopeConfig->getValue($path, $storeScope);
    }


    /**
     * @param $sendTo
     * @param $customer
     * @param $emailTemplate
     * @param $storeId
     * @param $sender
     *
     * @return bool
     */
    public function sendMail($sendTo, $data, $emailTemplate, $sender,$cc='')
    {
        try {
            /** @var Customer $mergedCustomerData */
            $sendTo = explode(',',$sendTo);
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($emailTemplate)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => 1,
                ])
                ->setTemplateVars($data)
                ->setFrom($sender)
                ->addTo($sendTo);
                if($cc){
                    $transport->addCc($cc);
                }
                $transport->getTransport()->sendMessage();
        } catch (Exception $e) {
            $this->_logger->critical($e->getMessage());
            echo $e->getMessage();
        }

        return false;
    }

    public function sendPdfMail($sendTo, $data, $emailTemplate, $storeId, $sender, $pdfFile)
    {

        try {
            /** @var Customer $mergedCustomerData */
            $sendTo = explode(',',$sendTo);
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($emailTemplate)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars($data)
                ->setFrom($sender)
                ->addTo($sendTo)
                ->addAttachment($pdfFile['file'],$pdfFile['file_name'], 'application/pdf')
                ->getTransport();
            $transport->sendMessage();
        } catch (Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        return false;
    }

    public function sendExcelMail($sendTo, $data, $emailTemplate, $storeId, $sender, $excelFile)
    {
        try {
            /** @var Customer $mergedCustomerData */
 
            $content = file_get_contents($excelFile['content']);
            $sendTo = explode(',',$sendTo);
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($emailTemplate)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars($data)
                ->setFrom($sender)
                ->addTo($sendTo)
                ->addAttachment($content,$excelFile['fileName'],'application/excel')
                ->getTransport();
            $transport->sendMessage();
        } catch (Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        return false;
    }

   }