<?php

namespace Swaminathan\Contact\Helper;

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
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
/**
 * Class AdminNotify
 * @package Shalby\Seller\Helper
 */
class AdminNotify extends AbstractHelper
{
    const CONFIG_MODULE_PATH = 'contactus';
    const XML_PATH_EMAIL = 'email';

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
     * @param $storeId
     * @return mixed
     */
    public function getModuleConfig($path, $storeId) {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->_scopeConfig->getValue(self::CONFIG_MODULE_PATH.'/'.$path, $storeScope);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEnabledNoticeAdmin($storeId = null)
    {
        return $this->getModuleConfig('admin_notification_email/enabled', $storeId);
    }

    public function getEnabledNoticeCustomer($storeId = null)
    {
        return $this->getModuleConfig('customer_notification_email/enabled', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getNoticeAdminTemplate($storeId = null)
    {
        return $this->getModuleConfig('admin_notification_email/template', $storeId);
    }

    public function getNoticeCustomerTemplate($storeId = null)
    {
        return $this->getModuleConfig('customer_notification_email/template', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getSenderAdmin($storeId = null)
    {
        return $this->getModuleConfig('admin_notification_email/sender', $storeId);
    }
    public function getSenderCustomer($storeId = null)
    {
        return $this->getModuleConfig('customer_notification_email/sender', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getRecipientsAdmin($storeId = null)
    {
        return preg_replace('/\s+/', '', $this->getModuleConfig('admin_notification_email/sendto', $storeId));
    }


    public function emailAcknowledgeCustomer($data){
       
        $storeId = $this->_storeManager->getStore()->getId();
        $sender = $this->getSenderCustomer($storeId);
        $sendTo = $data['email'];   

        if ($this->getEnabledNoticeCustomer()) {
                $test = $this->sendMail(
                    $sendTo,
                    $data,
                    $this->getNoticeCustomerTemplate(),
                    $storeId,
                    $sender
                );
        }
    }
    /**
     * @param $customer
     *
     * @throws NoSuchEntityException
     */
    public function emailNotifyAdmin($data)
    {   
        $storeId = $this->_storeManager->getStore()->getId();
        $sender = $this->getSenderAdmin($storeId);
        $recipient = $this->getRecipientsAdmin($storeId); 

        if ($this->getEnabledNoticeAdmin()) {
                $succ = $this->sendMail(
                    $recipient,
                    $data,
                    $this->getNoticeAdminTemplate(),
                    $storeId,
                    $sender
                );
            }
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
    public function sendMail($sendTo, $data, $emailTemplate, $storeId, $sender)
    {  

        try {
            /** @var Customer $mergedCustomerData */

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($emailTemplate)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars($data)                
                ->setFrom($sender)
                ->addTo($sendTo)
                ->getTransport();
            $transport->sendMessage();

            return true;
        } catch (Exception $e) {
            $this->_logger->critical($e->getMessage());
        }

        return false;
    }
   }
