<?php

namespace Swaminathan\Sms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Swaminathan\Sms\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;

/**
 * Class Shipment
 * @package Swaminathan\Sms\Observer
 */
class Shipment implements ObserverInterface
{
    /**
     *
     */
    const SMS_ADMIN_MOBILE = 'sms/general/mobilenumber';

    /**
     *
     */
    const SMS_SHIPMENT_ENABLE = 'sms/shipment/enabled';
    /**
     *
     */
    const SMS_SHIPMENT_MSGTOADMIN = 'sms/shipment/msgtoadmin';
    /**
     *
     */
    const SMS_SHIPMENT_SMSTEXT = 'sms/shipment/smstext';
    /**
     *
     */
    const SMS_SHIPMENT_SMSTEXTADMIN = 'sms/shipment/smstextadmin';

    /**
     *
     */
    const MSG91_TEMPLATE_ID = 'sms/shipment/templateid';
    /**
     *
     */
    const MSG91_TEMPLATE_ID_ADMIN = 'sms/shipment/templateidadmin';

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
     * Shipment constructor.
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $data
     * @param StoreManagerInterface $storeManager
     * @param PriceHelper $priceHelper
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        Data $data,
        StoreManagerInterface $storeManager,
        PriceHelper $priceHelper
    )
    {
        $this->logger = $logger;
        $this->helper = $data;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->priceHelper = $priceHelper;
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
                $enableInOrderPlace = $this->scopeConfig->getValue(self::SMS_SHIPMENT_ENABLE, $storeScope);
                $enableForadmin = $this->scopeConfig->getValue(self::SMS_SHIPMENT_MSGTOADMIN, $storeScope);

                if ($enableInOrderPlace) {
                    $adminMobiles = explode(',', $this->scopeConfig->getValue(self::SMS_ADMIN_MOBILE, $storeScope));
                    $msgText = $this->scopeConfig->getValue(self::SMS_SHIPMENT_SMSTEXT, $storeScope);
                    $temp_id = $this->scopeConfig->getValue(self::MSG91_TEMPLATE_ID, $storeScope);

                    $storeurl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);
                    $storename = $this->storeManager->getStore()->getName();

                    $shipment = $observer->getEvent()->getShipment();
                    $order = $shipment->getOrder();

                    $mobilenumber = $order->getBillingAddress()->getTelephone();
                    $countryId = $order->getBillingAddress()->getCountryId();
                    $countryCode = $this->helper->getCountryCode($countryId);
                    $mobilenumber = $countryCode . $mobilenumber;

                    $orderTotal = $this->priceHelper->currency($order->getGrandTotal(), true, false);

                    $trackNumber = $carrierName = array();
                    $tracksCollection = $shipment->getTracksCollection();
                    foreach ($tracksCollection->getItems() as $track) {
                        $trackNumber[] = $track->getTrackNumber();
                        $carrierName[] = $track->getTitle();
                    }

                    $trackNumber = implode(",", $trackNumber);
                    $carrierName = implode(",", $carrierName);

                    $codes = ['{{shop_name}}', '{{shop_url}}', '{{first_name}}', '{{last_name}}', '{{shipment_id}}', '{{order_id}}', '{{order_total}}', '{{track_no}}', '{{carrier_name}}'];
                    $accurate = [$storename, $storeurl, $order->getBillingAddress()->getFirstname(), $order->getBillingAddress()->getLastname(), '#' . $shipment->getIncrementId(), '#' . $order->getIncrementId(), $orderTotal, $trackNumber, $carrierName];

                    $finalContactText = str_replace($codes, $accurate, $msgText);

                    $this->helper->apiCall($finalContactText, $mobilenumber, $temp_id);

                    if ($enableForadmin) {
                        $msgText = $this->scopeConfig->getValue(self::SMS_SHIPMENT_SMSTEXTADMIN, $storeScope);
                        $codes = ['{{shop_name}}', '{{shop_url}}', '{{first_name}}', '{{last_name}}', '{{shipment_id}}', '{{order_id}}', '{{order_total}}', '{{track_no}}', '{{carrier_name}}'];
                        $accurate = [$storename, $storeurl, $order->getBillingAddress()->getFirstname(), $order->getBillingAddress()->getLastname(), '#' . $shipment->getIncrementId(), '#' . $order->getIncrementId(), $orderTotal, $trackNumber, $carrierName];

                        $finalContactText = str_replace($codes, $accurate, $msgText);
                        $temp_id_admin = $this->scopeConfig->getValue(self::MSG91_TEMPLATE_ID_ADMIN, $storeScope);
                        foreach ($adminMobiles as $adminMobile) {
                            $this->helper->apiCall($finalContactText, $adminMobile, $temp_id_admin);
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
