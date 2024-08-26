<?php

namespace Swaminathan\Customer\Controller\Actions;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseFactory;
use MiniOrange\MagentoSocialLogin\Helper\SocialUtility;
use Magento\Integration\Model\Oauth\TokenFactory;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;


/**
 * This class is called to log the customer user in. RelayState and
 * user are set separately. This is a simple class.
 */
class CustomerLoginAction extends \MiniOrange\MagentoSocialLogin\Controller\Actions\CustomerLoginAction
{
    private $user;
    private $customerSession;
    private $responseFactory;
    public function __construct(
        Context $context,
        SocialUtility $socialUtility,
        Session $customerSession,
        ResponseFactory $responseFactory,
        TokenFactory $tokenModelFactory,
        UrlHelper $urlHelper,

    ) {
        //You can use dependency injection to get any class this observer may need.
        parent::__construct($context, $socialUtility, $customerSession,$responseFactory);
        $this->urlHelper = $urlHelper;
        $this->tokenModelFactory =$tokenModelFactory;
             
    }


    /**
     * Execute function to execute the classes function.
     */
    public function execute()
    {
        $customerId = $this->user->getEntityId();
        $customerToken = $this->tokenModelFactory->create();
        $tokenKey = $customerToken->createCustomerToken($customerId)->getToken();
        $url =  $this->urlHelper->getReactUrl().'googlelogin/customer?token='.$tokenKey;
        $this->socialUtility->log_debug("CustomerLoginAction: execute");
        return $this->getResponse()->setRedirect($url)->sendResponse();
    }


     /** Setter for the user Parameter
      * @param $user
      * @return CustomerLoginAction
      */
    public function setUser($user)
    {
        $this->socialUtility->log_debug("CustomerLoginAction: setUser");
        $this->user = $user;
        return $this;
    }
}
