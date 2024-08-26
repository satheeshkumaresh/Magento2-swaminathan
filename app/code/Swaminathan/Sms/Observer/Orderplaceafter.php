<?php

namespace Swaminathan\Sms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Swaminathan\Sms\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class Orderplaceafter
 * @package Swaminathan\Sms\Observer
 */
class Orderplaceafter implements ObserverInterface
{
    /**
     *
     */
    const SMS_ADMIN_MOBILE = 'sms/general/mobilenumber';

    /**
     *
     */
    const SMS_ORDER_ENABLE = 'sms/order/enabled';
    /**
     *
     */
    const SMS_ORDER_MSGTOADMIN = 'sms/order/msgtoadmin';
    /**
     *
     */
    const SMS_ORDER_SMSTEXT = 'sms/order/smstext';
    /**
     *
     */
    const SMS_ORDER_SMSTEXTADMIN = 'sms/order/smstextadmin';

    /**
     *
     */
    const MSG91_TEMPLATE_ID = 'sms/order/templateid';
    /**
     *
     */
    const MSG91_TEMPLATE_ID_ADMIN = 'sms/order/templateidadmin';

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var PriceHelper
     */
    protected $priceHelper;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * Orderplaceafter constructor.
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $data
     * @param StoreManagerInterface $storeManager
     * @param PriceHelper $priceHelper
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        Data $data,
        StoreManagerInterface $storeManager,
        PriceHelper $priceHelper,
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->logger = $logger;
        $this->helper = $data;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->priceHelper = $priceHelper;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            if ($this->helper->smsEnable()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                $enableInOrderPlace = $this->scopeConfig->getValue(self::SMS_ORDER_ENABLE, $storeScope);
                $enableForadmin = $this->scopeConfig->getValue(self::SMS_ORDER_MSGTOADMIN, $storeScope);

                foreach ($observer->getEvent()->getOrderIds() as $order_ids) {
                    $order = $this->orderRepository->get($order_ids);
                    $storename = $this->storeManager->getStore()->getName();

                    $mobilenumber = $order->getBillingAddress()->getTelephone();
                    $countryId = $order->getBillingAddress()->getCountryId();
                    $countryCode = $this->helper->getCountryCode($countryId);
                    $mobilenumber = $countryCode . $mobilenumber;

                    if ($enableInOrderPlace) {

                        $adminMobiles = explode(',', $this->scopeConfig->getValue(self::SMS_ADMIN_MOBILE, $storeScope));

                        $msgText = $this->scopeConfig->getValue(self::SMS_ORDER_SMSTEXT, $storeScope);
                        $temp_id = $this->scopeConfig->getValue(self::MSG91_TEMPLATE_ID, $storeScope);
                        $storeurl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);
                        $orderTotal = $this->priceHelper->currency($order->getGrandTotal(), true, false);

                        $codes = ['{{shop_name}}', '{{shop_url}}', '{{first_name}}', '{{last_name}}', '{{order_id}}', '{{order_total}}'];
                        $accurate = [$storename, $storeurl, $order->getBillingAddress()->getFirstname(), $order->getBillingAddress()->getLastname(), '#' . $order->getIncrementId(), $orderTotal];

                        $finalContactText = str_replace($codes, $accurate, $msgText);

                        $this->helper->apiCall($finalContactText, $mobilenumber, $temp_id);

                        if ($enableForadmin) {
                            $msgText = $this->scopeConfig->getValue(self::SMS_ORDER_SMSTEXTADMIN, $storeScope);
                            $codes = ['{{shop_name}}', '{{shop_url}}', '{{first_name}}', '{{last_name}}', '{{order_id}}', '{{order_total}}'];
                            $accurate = [$storename, $storeurl, $order->getBillingAddress()->getFirstname(), $order->getBillingAddress()->getLastname(), '#' . $order->getIncrementId(), $orderTotal];
                            $finalContactText = str_replace($codes, $accurate, $msgText);
                            $temp_id_admin = $this->scopeConfig->getValue(self::MSG91_TEMPLATE_ID_ADMIN, $storeScope);
                            foreach ($adminMobiles as $adminMobile) {
                                $this->helper->apiCall($finalContactText, $adminMobile, $temp_id_admin);
                            }
                        }
                    }
                }

            }
            return true;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
