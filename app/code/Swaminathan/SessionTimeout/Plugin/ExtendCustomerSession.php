<?php
namespace Swaminathan\SessionTimeout\Plugin;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\Config\ConfigInterface as SessionConfig;

class ExtendCustomerSession
{
    /**
     * @var SessionConfig
     */
    private $sessionConfig;

    /**
     * @param SessionConfig $sessionConfig
     */
    public function __construct(SessionConfig $sessionConfig)
    {
        $this->sessionConfig = $sessionConfig;
    }

    /**
     * Extend the customer session timeout.
     *
     * @param CustomerSession $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundSetCustomerData(
        CustomerSession $subject, 
        \Closure $proceed,
        $customerData
    )
    {
        $lifetime = 60 * 60 * 24 * 7; // 7 days in seconds
        $this->sessionConfig->setCookieLifetime($lifetime);
        $this->sessionConfig->setCookiePath('/');
        $this->sessionConfig->setCookieHttpOnly(true);
        $this->sessionConfig->setUseCookies(true);
        $this->sessionConfig->setUseOnlyCookies(true);
        $this->sessionConfig->setCookieSameSite('Lax');
        $result = $proceed($customerData);
        return $result;
    }
}
