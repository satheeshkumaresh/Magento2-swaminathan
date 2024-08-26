<?php

namespace Swaminathan\Sms\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package Swaminathan\Sms\Helper
 */
class Data extends AbstractHelper
{
    /**
     *
     */
    const SMS_ENABLE = 'sms/general/enabled';
    /**
     *
     */
    const SMS_APIPROVIDER = 'sms/apicredential/apiprovider';
    /**
     *
     */
    const SMS_APIKEY = 'sms/apicredential/apikey';
    /**
     *
     */
    const SMS_SENDERID = 'sms/apicredential/senderid';
    /**
     *
     */
    const SMS_APIURL = 'sms/apicredential/apiurl';
    /**
     *
     */
    const SMS_MSGTYPE = 'sms/apicredential/msgtype';
    /**
     *
     */
    const SMS_URL = 'sms/apicredential/customurl';

    /**
     *
     */
    const TWILLIOSID = 'sms/apicredential/twiliosid';
    /**
     *
     */
    const TWILLIOTOKEN = 'sms/apicredential/twiliotoken';
    /**
     *
     */
    const TWILLIONUMBER = 'sms/apicredential/twilionumber';


    const BULKSMS_USERNAME = 'sms/apicredential/username';
    const BULKSMS_PASSWORD = 'sms/apicredential/password';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    )
    {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function smsEnable()
    {
        return $this->scopeConfig->getValue(self::SMS_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $storeId
     * @return mixed
     */
    public function getStoreURL($storeId)
    {
        return $this->scopeConfig->getValue('web/secure/base_url', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $msg
     * @param $mobilenumber
     * @param null $tmpid
     * @param int $testing
     * @param int $otp
     * @return string
     */
    public function apiCall($msg, $mobilenumber, $tmpid = null, $testing = 0, $otp = 0)
    {
        $mobilenumber = str_replace('+', '', $mobilenumber);
        $mobilenumber = trim(str_replace(' ', '', $mobilenumber));

        $this->customLog("=================================================");
        $this->customLog("apiCall call");
        $this->customLog($msg);
        $this->customLog($mobilenumber);

        try {
            if ($this->smsEnable()) {

                $apiProvider = $this->scopeConfig->getValue(self::SMS_APIPROVIDER, ScopeInterface::SCOPE_STORE);
                $apikey = $this->scopeConfig->getValue(self::SMS_APIKEY, ScopeInterface::SCOPE_STORE);
                $senderid = $this->scopeConfig->getValue(self::SMS_SENDERID, ScopeInterface::SCOPE_STORE);
                $url = $this->scopeConfig->getValue(self::SMS_APIURL, ScopeInterface::SCOPE_STORE);
                $msgtype = $this->scopeConfig->getValue(self::SMS_MSGTYPE, ScopeInterface::SCOPE_STORE);

                if ($apiProvider == 'msg') {

                    $msg = urlencode($msg);
                    $postUrl = $url . "?authkey=" . $apikey . "&sender=" . $senderid . "&mobiles=" . $mobilenumber . "&route=" . $msgtype . "&message=" . $msg . "&DLT_TE_ID=" . $tmpid;

                    $curl = curl_init();

                    curl_setopt_array($curl, [
                        CURLOPT_URL => $postUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => 0,
                    ]);

                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);
                    if ($err) {
                        $this->customLog($err);
                        if ($testing) {
                            return $err;
                        } elseif ($otp) {
                            return 0;
                        }
                    } else {
                        $this->customLog($response);
                        if ($testing) {
                            return $response;
                        } elseif ($otp) {
                            return 1;
                        }
                    }
                } else if ($apiProvider == 'twilio') {

                    $sid = $this->scopeConfig->getValue(self::TWILLIOSID, ScopeInterface::SCOPE_STORE);
                    $token = $this->scopeConfig->getValue(self::TWILLIOTOKEN, ScopeInterface::SCOPE_STORE);
                    $number = $this->scopeConfig->getValue(self::TWILLIONUMBER, ScopeInterface::SCOPE_STORE);

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $twilio = $objectManager->get('\Twilio\Rest\ClientFactory');
                    $client = $twilio->create([
                        'username' => $sid,
                        'password' => $token
                    ]);

                    $response = $client->messages->create(
                        '+' . $mobilenumber,
                        array(
                            'from' => '+' . $number,
                            'body' => $msg
                        )
                    );
                    if ($testing) {
                        return $response;
                    } elseif ($otp) {
                        if ($response->sid) {
                            return 1;
                        } else {
                            return 0;
                        }
                    }
                } else if ($apiProvider == 'bulksms') {


                    $messages = array(
                        array('to' => $mobilenumber, 'body' => $msg)
                    );
                    $post_body = json_encode($messages);
                    $url = "https://api.bulksms.com/v1/messages?auto-unicode=true&longMessageMaxParts=30";
                    $username = $this->scopeConfig->getValue(self::BULKSMS_USERNAME, ScopeInterface::SCOPE_STORE);
                    $password = $this->scopeConfig->getValue(self::BULKSMS_PASSWORD, ScopeInterface::SCOPE_STORE);

                    $ch = curl_init();
                    $headers = array(
                        'Content-Type:application/json',
                        'Authorization:Basic ' . base64_encode("$username:$password")
                    );
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

                    $response = curl_exec($ch);
                    $err = curl_error($ch);
                    curl_close($ch);
                    if ($err) {
                        $this->customLog($err);
                        if ($testing) {
                            return $err;
                        } elseif ($otp) {
                            return 0;
                        }
                    } else {
                        $this->customLog($response);
                        if ($testing) {
                            return $response;
                        } elseif ($otp) {
                            return 1;
                        }
                    }

                } else if ($apiProvider == 'other') {
                    $customUrl = $this->scopeConfig->getValue(self::SMS_URL, ScopeInterface::SCOPE_STORE);
                    $msg = urlencode($msg);
                    $codes = ['{mobile}', '{msg}'];
                    $accurate = [$mobilenumber, $msg];
                    $postUrl = str_replace($codes, $accurate, $customUrl);

                    $curl = curl_init();

                    curl_setopt_array($curl, [
                        CURLOPT_URL => $postUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => 0,
                    ]);

                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);

                    if ($err) {
                        $this->customLog($err);
                        if ($testing) {
                            return $err;
                        } elseif ($otp) {
                            return 0;
                        }
                    } else {
                        $this->customLog($response);
                        if ($testing) {
                            return $response;
                        } elseif ($otp) {
                            return 1;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->customLog($e->getMessage());
            if ($testing) {
                return $e->getMessage();
            }
        }
    }

    /**
     * @param $log
     * @throws \Zend_Log_Exception
     */
    public function customLog($log)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/sms.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(print_r($log, true));
    }

    /**
     * @param $countryid
     * @return string
     */
    public function getCountryCode($countryid)
    {
        $countryArray = [
            'AD' => ['name' => 'ANDORRA', 'code' => '376'],
            'AE' => ['name' => 'UNITED ARAB EMIRATES', 'code' => '971'],
            'AF' => ['name' => 'AFGHANISTAN', 'code' => '93'],
            'AG' => ['name' => 'ANTIGUA AND BARBUDA', 'code' => '1268'],
            'AI' => ['name' => 'ANGUILLA', 'code' => '1264'],
            'AL' => ['name' => 'ALBANIA', 'code' => '355'],
            'AM' => ['name' => 'ARMENIA', 'code' => '374'],
            'AN' => ['name' => 'NETHERLANDS ANTILLES', 'code' => '599'],
            'AO' => ['name' => 'ANGOLA', 'code' => '244'],
            'AQ' => ['name' => 'ANTARCTICA', 'code' => '672'],
            'AR' => ['name' => 'ARGENTINA', 'code' => '54'],
            'AS' => ['name' => 'AMERICAN SAMOA', 'code' => '1684'],
            'AT' => ['name' => 'AUSTRIA', 'code' => '43'],
            'AU' => ['name' => 'AUSTRALIA', 'code' => '61'],
            'AW' => ['name' => 'ARUBA', 'code' => '297'],
            'AZ' => ['name' => 'AZERBAIJAN', 'code' => '994'],
            'BA' => ['name' => 'BOSNIA AND HERZEGOVINA', 'code' => '387'],
            'BB' => ['name' => 'BARBADOS', 'code' => '1246'],
            'BD' => ['name' => 'BANGLADESH', 'code' => '880'],
            'BE' => ['name' => 'BELGIUM', 'code' => '32'],
            'BF' => ['name' => 'BURKINA FASO', 'code' => '226'],
            'BG' => ['name' => 'BULGARIA', 'code' => '359'],
            'BH' => ['name' => 'BAHRAIN', 'code' => '973'],
            'BI' => ['name' => 'BURUNDI', 'code' => '257'],
            'BJ' => ['name' => 'BENIN', 'code' => '229'],
            'BL' => ['name' => 'SAINT BARTHELEMY', 'code' => '590'],
            'BM' => ['name' => 'BERMUDA', 'code' => '1441'],
            'BN' => ['name' => 'BRUNEI DARUSSALAM', 'code' => '673'],
            'BO' => ['name' => 'BOLIVIA', 'code' => '591'],
            'BR' => ['name' => 'BRAZIL', 'code' => '55'],
            'BS' => ['name' => 'BAHAMAS', 'code' => '1242'],
            'BT' => ['name' => 'BHUTAN', 'code' => '975'],
            'BW' => ['name' => 'BOTSWANA', 'code' => '267'],
            'BY' => ['name' => 'BELARUS', 'code' => '375'],
            'BZ' => ['name' => 'BELIZE', 'code' => '501'],
            'CA' => ['name' => 'CANADA', 'code' => '1'],
            'CC' => ['name' => 'COCOS (KEELING) ISLANDS', 'code' => '61'],
            'CD' => ['name' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE', 'code' => '243'],
            'CF' => ['name' => 'CENTRAL AFRICAN REPUBLIC', 'code' => '236'],
            'CG' => ['name' => 'CONGO', 'code' => '242'],
            'CH' => ['name' => 'SWITZERLAND', 'code' => '41'],
            'CI' => ['name' => 'COTE D IVOIRE', 'code' => '225'],
            'CK' => ['name' => 'COOK ISLANDS', 'code' => '682'],
            'CL' => ['name' => 'CHILE', 'code' => '56'],
            'CM' => ['name' => 'CAMEROON', 'code' => '237'],
            'CN' => ['name' => 'CHINA', 'code' => '86'],
            'CO' => ['name' => 'COLOMBIA', 'code' => '57'],
            'CR' => ['name' => 'COSTA RICA', 'code' => '506'],
            'CU' => ['name' => 'CUBA', 'code' => '53'],
            'CV' => ['name' => 'CAPE VERDE', 'code' => '238'],
            'CX' => ['name' => 'CHRISTMAS ISLAND', 'code' => '61'],
            'CY' => ['name' => 'CYPRUS', 'code' => '357'],
            'CZ' => ['name' => 'CZECH REPUBLIC', 'code' => '420'],
            'DE' => ['name' => 'GERMANY', 'code' => '49'],
            'DJ' => ['name' => 'DJIBOUTI', 'code' => '253'],
            'DK' => ['name' => 'DENMARK', 'code' => '45'],
            'DM' => ['name' => 'DOMINICA', 'code' => '1767'],
            'DO' => ['name' => 'DOMINICAN REPUBLIC', 'code' => '1809'],
            'DZ' => ['name' => 'ALGERIA', 'code' => '213'],
            'EC' => ['name' => 'ECUADOR', 'code' => '593'],
            'EE' => ['name' => 'ESTONIA', 'code' => '372'],
            'EG' => ['name' => 'EGYPT', 'code' => '20'],
            'ER' => ['name' => 'ERITREA', 'code' => '291'],
            'ES' => ['name' => 'SPAIN', 'code' => '34'],
            'ET' => ['name' => 'ETHIOPIA', 'code' => '251'],
            'FI' => ['name' => 'FINLAND', 'code' => '358'],
            'FJ' => ['name' => 'FIJI', 'code' => '679'],
            'FK' => ['name' => 'FALKLAND ISLANDS (MALVINAS)', 'code' => '500'],
            'FM' => ['name' => 'MICRONESIA, FEDERATED STATES OF', 'code' => '691'],
            'FO' => ['name' => 'FAROE ISLANDS', 'code' => '298'],
            'FR' => ['name' => 'FRANCE', 'code' => '33'],
            'GA' => ['name' => 'GABON', 'code' => '241'],
            'GB' => ['name' => 'UNITED KINGDOM', 'code' => '44'],
            'GD' => ['name' => 'GRENADA', 'code' => '1473'],
            'GE' => ['name' => 'GEORGIA', 'code' => '995'],
            'GH' => ['name' => 'GHANA', 'code' => '233'],
            'GI' => ['name' => 'GIBRALTAR', 'code' => '350'],
            'GL' => ['name' => 'GREENLAND', 'code' => '299'],
            'GM' => ['name' => 'GAMBIA', 'code' => '220'],
            'GN' => ['name' => 'GUINEA', 'code' => '224'],
            'GQ' => ['name' => 'EQUATORIAL GUINEA', 'code' => '240'],
            'GR' => ['name' => 'GREECE', 'code' => '30'],
            'GT' => ['name' => 'GUATEMALA', 'code' => '502'],
            'GU' => ['name' => 'GUAM', 'code' => '1671'],
            'GW' => ['name' => 'GUINEA-BISSAU', 'code' => '245'],
            'GY' => ['name' => 'GUYANA', 'code' => '592'],
            'HK' => ['name' => 'HONG KONG', 'code' => '852'],
            'HN' => ['name' => 'HONDURAS', 'code' => '504'],
            'HR' => ['name' => 'CROATIA', 'code' => '385'],
            'HT' => ['name' => 'HAITI', 'code' => '509'],
            'HU' => ['name' => 'HUNGARY', 'code' => '36'],
            'ID' => ['name' => 'INDONESIA', 'code' => '62'],
            'IE' => ['name' => 'IRELAND', 'code' => '353'],
            'IL' => ['name' => 'ISRAEL', 'code' => '972'],
            'IM' => ['name' => 'ISLE OF MAN', 'code' => '44'],
            'IN' => ['name' => 'INDIA', 'code' => '91'],
            'IQ' => ['name' => 'IRAQ', 'code' => '964'],
            'IR' => ['name' => 'IRAN, ISLAMIC REPUBLIC OF', 'code' => '98'],
            'IS' => ['name' => 'ICELAND', 'code' => '354'],
            'IT' => ['name' => 'ITALY', 'code' => '39'],
            'JM' => ['name' => 'JAMAICA', 'code' => '1876'],
            'JO' => ['name' => 'JORDAN', 'code' => '962'],
            'JP' => ['name' => 'JAPAN', 'code' => '81'],
            'KE' => ['name' => 'KENYA', 'code' => '254'],
            'KG' => ['name' => 'KYRGYZSTAN', 'code' => '996'],
            'KH' => ['name' => 'CAMBODIA', 'code' => '855'],
            'KI' => ['name' => 'KIRIBATI', 'code' => '686'],
            'KM' => ['name' => 'COMOROS', 'code' => '269'],
            'KN' => ['name' => 'SAINT KITTS AND NEVIS', 'code' => '1869'],
            'KP' => ['name' => 'KOREA DEMOCRATIC PEOPLES REPUBLIC OF', 'code' => '850'],
            'KR' => ['name' => 'KOREA REPUBLIC OF', 'code' => '82'],
            'KW' => ['name' => 'KUWAIT', 'code' => '965'],
            'KY' => ['name' => 'CAYMAN ISLANDS', 'code' => '1345'],
            'KZ' => ['name' => 'KAZAKSTAN', 'code' => '7'],
            'LA' => ['name' => 'LAO PEOPLES DEMOCRATIC REPUBLIC', 'code' => '856'],
            'LB' => ['name' => 'LEBANON', 'code' => '961'],
            'LC' => ['name' => 'SAINT LUCIA', 'code' => '1758'],
            'LI' => ['name' => 'LIECHTENSTEIN', 'code' => '423'],
            'LK' => ['name' => 'SRI LANKA', 'code' => '94'],
            'LR' => ['name' => 'LIBERIA', 'code' => '231'],
            'LS' => ['name' => 'LESOTHO', 'code' => '266'],
            'LT' => ['name' => 'LITHUANIA', 'code' => '370'],
            'LU' => ['name' => 'LUXEMBOURG', 'code' => '352'],
            'LV' => ['name' => 'LATVIA', 'code' => '371'],
            'LY' => ['name' => 'LIBYAN ARAB JAMAHIRIYA', 'code' => '218'],
            'MA' => ['name' => 'MOROCCO', 'code' => '212'],
            'MC' => ['name' => 'MONACO', 'code' => '377'],
            'MD' => ['name' => 'MOLDOVA, REPUBLIC OF', 'code' => '373'],
            'ME' => ['name' => 'MONTENEGRO', 'code' => '382'],
            'MF' => ['name' => 'SAINT MARTIN', 'code' => '1599'],
            'MG' => ['name' => 'MADAGASCAR', 'code' => '261'],
            'MH' => ['name' => 'MARSHALL ISLANDS', 'code' => '692'],
            'MK' => ['name' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'code' => '389'],
            'ML' => ['name' => 'MALI', 'code' => '223'],
            'MM' => ['name' => 'MYANMAR', 'code' => '95'],
            'MN' => ['name' => 'MONGOLIA', 'code' => '976'],
            'MO' => ['name' => 'MACAU', 'code' => '853'],
            'MP' => ['name' => 'NORTHERN MARIANA ISLANDS', 'code' => '1670'],
            'MR' => ['name' => 'MAURITANIA', 'code' => '222'],
            'MS' => ['name' => 'MONTSERRAT', 'code' => '1664'],
            'MT' => ['name' => 'MALTA', 'code' => '356'],
            'MU' => ['name' => 'MAURITIUS', 'code' => '230'],
            'MV' => ['name' => 'MALDIVES', 'code' => '960'],
            'MW' => ['name' => 'MALAWI', 'code' => '265'],
            'MX' => ['name' => 'MEXICO', 'code' => '52'],
            'MY' => ['name' => 'MALAYSIA', 'code' => '60'],
            'MZ' => ['name' => 'MOZAMBIQUE', 'code' => '258'],
            'NA' => ['name' => 'NAMIBIA', 'code' => '264'],
            'NC' => ['name' => 'NEW CALEDONIA', 'code' => '687'],
            'NE' => ['name' => 'NIGER', 'code' => '227'],
            'NG' => ['name' => 'NIGERIA', 'code' => '234'],
            'NI' => ['name' => 'NICARAGUA', 'code' => '505'],
            'NL' => ['name' => 'NETHERLANDS', 'code' => '31'],
            'NO' => ['name' => 'NORWAY', 'code' => '47'],
            'NP' => ['name' => 'NEPAL', 'code' => '977'],
            'NR' => ['name' => 'NAURU', 'code' => '674'],
            'NU' => ['name' => 'NIUE', 'code' => '683'],
            'NZ' => ['name' => 'NEW ZEALAND', 'code' => '64'],
            'OM' => ['name' => 'OMAN', 'code' => '968'],
            'PA' => ['name' => 'PANAMA', 'code' => '507'],
            'PE' => ['name' => 'PERU', 'code' => '51'],
            'PF' => ['name' => 'FRENCH POLYNESIA', 'code' => '689'],
            'PG' => ['name' => 'PAPUA NEW GUINEA', 'code' => '675'],
            'PH' => ['name' => 'PHILIPPINES', 'code' => '63'],
            'PK' => ['name' => 'PAKISTAN', 'code' => '92'],
            'PL' => ['name' => 'POLAND', 'code' => '48'],
            'PM' => ['name' => 'SAINT PIERRE AND MIQUELON', 'code' => '508'],
            'PN' => ['name' => 'PITCAIRN', 'code' => '870'],
            'PR' => ['name' => 'PUERTO RICO', 'code' => '1'],
            'PT' => ['name' => 'PORTUGAL', 'code' => '351'],
            'PW' => ['name' => 'PALAU', 'code' => '680'],
            'PY' => ['name' => 'PARAGUAY', 'code' => '595'],
            'QA' => ['name' => 'QATAR', 'code' => '974'],
            'RO' => ['name' => 'ROMANIA', 'code' => '40'],
            'RS' => ['name' => 'SERBIA', 'code' => '381'],
            'RU' => ['name' => 'RUSSIAN FEDERATION', 'code' => '7'],
            'RW' => ['name' => 'RWANDA', 'code' => '250'],
            'SA' => ['name' => 'SAUDI ARABIA', 'code' => '966'],
            'SB' => ['name' => 'SOLOMON ISLANDS', 'code' => '677'],
            'SC' => ['name' => 'SEYCHELLES', 'code' => '248'],
            'SD' => ['name' => 'SUDAN', 'code' => '249'],
            'SE' => ['name' => 'SWEDEN', 'code' => '46'],
            'SG' => ['name' => 'SINGAPORE', 'code' => '65'],
            'SH' => ['name' => 'SAINT HELENA', 'code' => '290'],
            'SI' => ['name' => 'SLOVENIA', 'code' => '386'],
            'SK' => ['name' => 'SLOVAKIA', 'code' => '421'],
            'SL' => ['name' => 'SIERRA LEONE', 'code' => '232'],
            'SM' => ['name' => 'SAN MARINO', 'code' => '378'],
            'SN' => ['name' => 'SENEGAL', 'code' => '221'],
            'SO' => ['name' => 'SOMALIA', 'code' => '252'],
            'SR' => ['name' => 'SURINAME', 'code' => '597'],
            'ST' => ['name' => 'SAO TOME AND PRINCIPE', 'code' => '239'],
            'SV' => ['name' => 'EL SALVADOR', 'code' => '503'],
            'SY' => ['name' => 'SYRIAN ARAB REPUBLIC', 'code' => '963'],
            'SZ' => ['name' => 'SWAZILAND', 'code' => '268'],
            'TC' => ['name' => 'TURKS AND CAICOS ISLANDS', 'code' => '1649'],
            'TD' => ['name' => 'CHAD', 'code' => '235'],
            'TG' => ['name' => 'TOGO', 'code' => '228'],
            'TH' => ['name' => 'THAILAND', 'code' => '66'],
            'TJ' => ['name' => 'TAJIKISTAN', 'code' => '992'],
            'TK' => ['name' => 'TOKELAU', 'code' => '690'],
            'TL' => ['name' => 'TIMOR-LESTE', 'code' => '670'],
            'TM' => ['name' => 'TURKMENISTAN', 'code' => '993'],
            'TN' => ['name' => 'TUNISIA', 'code' => '216'],
            'TO' => ['name' => 'TONGA', 'code' => '676'],
            'TR' => ['name' => 'TURKEY', 'code' => '90'],
            'TT' => ['name' => 'TRINIDAD AND TOBAGO', 'code' => '1868'],
            'TV' => ['name' => 'TUVALU', 'code' => '688'],
            'TW' => ['name' => 'TAIWAN, PROVINCE OF CHINA', 'code' => '886'],
            'TZ' => ['name' => 'TANZANIA, UNITED REPUBLIC OF', 'code' => '255'],
            'UA' => ['name' => 'UKRAINE', 'code' => '380'],
            'UG' => ['name' => 'UGANDA', 'code' => '256'],
            'US' => ['name' => 'UNITED STATES', 'code' => '1'],
            'UY' => ['name' => 'URUGUAY', 'code' => '598'],
            'UZ' => ['name' => 'UZBEKISTAN', 'code' => '998'],
            'VA' => ['name' => 'HOLY SEE (VATICAN CITY STATE)', 'code' => '39'],
            'VC' => ['name' => 'SAINT VINCENT AND THE GRENADINES', 'code' => '1784'],
            'VE' => ['name' => 'VENEZUELA', 'code' => '58'],
            'VG' => ['name' => 'VIRGIN ISLANDS, BRITISH', 'code' => '1284'],
            'VI' => ['name' => 'VIRGIN ISLANDS, U.S.', 'code' => '1340'],
            'VN' => ['name' => 'VIET NAM', 'code' => '84'],
            'VU' => ['name' => 'VANUATU', 'code' => '678'],
            'WF' => ['name' => 'WALLIS AND FUTUNA', 'code' => '681'],
            'WS' => ['name' => 'SAMOA', 'code' => '685'],
            'XK' => ['name' => 'KOSOVO', 'code' => '381'],
            'YE' => ['name' => 'YEMEN', 'code' => '967'],
            'YT' => ['name' => 'MAYOTTE', 'code' => '262'],
            'ZA' => ['name' => 'SOUTH AFRICA', 'code' => '27'],
            'ZM' => ['name' => 'ZAMBIA', 'code' => '260'],
            'ZW' => ['name' => 'ZIMBABWE', 'code' => '263']
        ];

        if (array_key_exists($countryid, $countryArray)) {
            return $countryArray[$countryid]['code'];
        }
        return '';
    }
}
