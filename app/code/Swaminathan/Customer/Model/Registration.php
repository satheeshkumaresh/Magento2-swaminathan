<?php

namespace Swaminathan\Customer\Model;
use Magento\Store\Model\ScopeInterface;
use Swaminathan\Customer\Api\RegistrationInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Integration\Model\CredentialsValidator;
use Magento\Integration\Model\Oauth\Token\RequestThrottler;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Event\ManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Magento\User\Helper\Data as UserHelper;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Swaminathan\NotifyMail\Helper\MailNotify;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Customer\Model\ForgotPasswordToken\GetCustomerByToken;
use Magento\Customer\Model\Customer\CredentialsValidator as CustomerCredentialValidator;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Exception\State\ExpiredException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use MiniOrange\MagentoSocialLogin\Helper\SocialConstants;
use Swaminathan\CmsPlpPdp\Helper\UrlHelper;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Registration implements RegistrationInterface
{

    const ACCOUNT_CONFIRMATION_REQUIRED = 'customer/create_account/confirm';

    const XML_PATH_MINIMUM_PASSWORD_LENGTH = 'customer/password/minimum_password_length';
    const XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER = 'customer/password/required_character_classes_number';
    const MAX_PASSWORD_LENGTH = 256;

    const REGISTRATION_CUSTOMER_ENABLED = "registration/customer_registration_notification_email/enabled";
    const REGISTRATION_CUSTOMER_SENDER = "registration/customer_registration_notification_email/sender";
    const REGISTRATION_CUSTOMER_TEMPLATE = "registration/customer_registration_notification_email/template";

    const CONFIRM_CUSTOMER_ENABLED = "registration/customer_confirmation_notification_email/enabled";
    const CONFIRM_CUSTOMER_SENDER = "registration/customer_confirmation_notification_email/sender";
    const CONFIRM_CUSTOMER_TEMPLATE = "registration/customer_confirmation_notification_email/template";
    const CONFIRM_CUSTOMER_SUPPORT = "registration/customer_confirmation_notification_email/support";

    const FORGOT_PASSWORD_CUSTOMER_ENABLED = "registration/customer_forgot_password_notification_email/enabled";
    const FORGOT_PASSWORD_CUSTOMER_SENDER = "registration/customer_forgot_password_notification_email/sender";
    const FORGOT_PASSWORD_CUSTOMER_TEMPLATE = "registration/customer_forgot_password_notification_email/template";


    protected $timezoneInterface;
    protected $collectionFactory;
    /**
     * @param Context     $context
     * @param JsonFactory $resultJsonFactory
     */

    
    public function __construct(
        CustomerFactory $customerFactory,
        Customer $customerModel,
        DateTime $dateTime,
        AccountManagementInterface $accountManagementInterface,
        CredentialsValidator $validatorHelper,
        ManagerInterface $eventManager = null,
        RequestThrottler $requestThrottler,
        TokenModelFactory $tokenModelFactory,
        CustomerRepositoryInterface $customerRepository,
        UserHelper $user_helper,
        CustomerRegistry $customerRegistry,
        ScopeConfigInterface $scopeConfigInterface,
        StoreManagerInterface $storeManager,
        MailNotify $mailNotify,
        StringUtils $stringHelper,
        GetCustomerByToken $getByToken,
        CustomerCredentialValidator $credentialsValidator,
        DateTimeFactory $dataTimeFactory,
        EncryptorInterface $encryptor,
        UrlHelper $urlHelper,
        LoggerInterface $logger,
        CollectionFactory $collectionFactory,
        TimezoneInterface $timezoneInterface
        
        ){
        $this->customerFactory = $customerFactory;
        $this->customerModel = $customerModel;
        $this->dateTime = $dateTime;
        $this->accountManagementInterface = $accountManagementInterface;
        $this->validatorHelper = $validatorHelper;
        $this->eventManager = $eventManager ?: \Magento\Framework\App\ObjectManager::getInstance()
        ->get(ManagerInterface::class);
        $this->requestThrottler = $requestThrottler;
        $this->tokenModelFactory = $tokenModelFactory;
        $this->customerRepository = $customerRepository;
        $this->user_helper = $user_helper;
        $this->customerRegistry = $customerRegistry;
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManager = $storeManager;
        $this->mailNotify = $mailNotify;
        $this->stringHelper = $stringHelper;
        $this->getByToken = $getByToken;
        $this->credentialsValidator = $credentialsValidator;
        $this->dataTimeFactory =  $dataTimeFactory;
        $this->encryptor = $encryptor;
        $this->urlHelper = $urlHelper;
        $this->logger =  $logger;
        $this->collectionFactory = $collectionFactory;
        $this->timezoneInterface = $timezoneInterface;
    }    

    public function validate_mobile($mobile)
    {
        return preg_match('/^[0-9]{10}+$/', $mobile);
    }
    
    /**
     * Create Swaminathan Customer
     * @param string[] $customer
     * @return array
     */
    public function createCustomer($customer)
    {

    
        //validate First Name
        if(!isset($customer['firstname']) || empty($customer['firstname'])){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"Please Enter the First Name"]; 
            return $response;
        }

        //validate Last Name
        if(!isset($customer['lastname']) || empty($customer['lastname'])){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"Please Enter the Last Name"]; 
            return $response;
        }

        //Password Validation
        if(!isset($customer['password']) || empty($customer['password'])){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"Please Enter Password."]; 
            return $response;
        }

         //Confirm Password Validation
        if(!isset($customer['confirm_password']) || empty($customer['confirm_password'])){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"Please Enter Confirm Password."]; 
            return $response;
        }

        //Validating Confirm Password as same as Password
        if(!($customer['password'] == $customer['confirm_password'])){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"Confirm Password should be same as Password."]; 
            return $response;
        }

        //Password Length Validation
        $credentialValidator = $this->credentialsValidator->checkPasswordDifferentFromEmail(
            $customer['email'],
            $customer['password']
        );
        $passwordStrength = $this->checkPasswordStrength($customer['password']);

        // Validating Existing Email
         $customerCollection = $this->customerModel->getCollection()->addFieldToFilter('email',$customer['email']);
         if(count($customerCollection) > 0){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"E-mail address already registered."]; 
            return $response;
        }

        // User Registration Begins
        try{ 
            $gmtDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $customerNew = $this->customerFactory->create();
            $customerNew->setFirstname($customer['firstname']);
            $customerNew->setLastname($customer['lastname']);
            $customerNew->setEmail($customer['email']);
            if(isset($customer['mobile'])){
                $customerNew->setMobile($customer['mobile']);
            }
            $customerNew->setPassword($customer['password']);
            $customerNew->setGroupId(1);
            $customerNew->setWebsiteId(1);
            $customerNew->setStoreId(1);
            $customerNew->setCreatedAt($gmtDate);
            $customerNew->setUpdatedAt($gmtDate);
            $customerNew->save();
            try { 
                $mailArray['store_id'] = $customer['store_id'];
                $mailArray['email'] = $customer['email'];
                $mailArray['firstname'] = $customer['firstname'];
                $mailArray['lastname'] = $customer['lastname'];
                $mailArray['name'] = $customer['firstname']. ' ' . $customer['lastname'];
                $reactUrl = $this->urlHelper->getReactUrl();
                $mailArray['store_url'] = $reactUrl;
                $mailArray['confirmation_link'] = $reactUrl.'accountsuccess?key='.$customerNew->getConfirmation();
                $this->sendNotificationMail($mailArray);
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }


            $response[] = [
                "code" => 200,
                "status" => true,
                "message" => "<div class='signup-message'><p class='thankyou'>Thankyou for signing up, please confirm your email.</p>

                           <p class='message'>We've emailed you a confirmation link. Once you confirm
                          your email, you can continue setting up your profile & place 
                                                    orders.</p>

                                              <p class='footer'>Thanks!</p></div>",
                
            ];
        } catch (\Exception $e) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => $e->getMessage()
            ];
        }
        
        return $response;
    }

    /**
     * Send Acccount Confirmation Email 
     * @param string $email
     * @return array
     */
    public function sendAccountConfirmationEmail($email)
    {
        try{ 
            // $gmtDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
            $customerModelData = $this->customerModel;
            $customerModelData->setWebsiteId(1); 
            $customerModelData->loadByEmail($email);
            $customersData = $customerModelData->getData();
            if(!empty($customersData)){
                try { 
                    $mailArray['store_id'] = $customersData['store_id'];
                    $mailArray['email'] = $customersData['email'];
                    $mailArray['firstname'] = $customersData['firstname'];
                    $mailArray['lastname'] = $customersData['lastname'];
                    $mailArray['name'] = $customersData['firstname']. ' ' . $customersData['lastname'];
                    $reactUrl = $this->urlHelper->getReactUrl();
                    $mailArray['store_url'] = $reactUrl;
                    $mailArray['confirmation_link'] = $reactUrl.'accountsuccess?key='.$customerModelData->getConfirmation();
                    $this->sendNotificationMail($mailArray);
                } catch (\Exception $e) {
                    $this->logger->info($e->getMessage());
                }
                $response[] = [
                    "code" => 200,
                    "status" => true,
                    "message" => "Account confirmation email has been sent successfully. Please Confirm the Email Id.",
                ];
            }
            else{
                $response[] = [
                    "code" => 200,
                    "status" => false,
                    "message" => "Email Id is not registered with Sri Swaminathan & Co.",
                ];
            }
        } catch (\Exception $e) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => $e->getMessage()
            ];
        }
        
        return $response;
    }

    /**
     * Confirm Swaminathan Customer
     * @param string $confirmationKey
     * @return array
     */
    public function confirmEmail($confirmationKey){
        try{
            $customerCollectionFactory = $this->collectionFactory->create()
                                                ->addAttributeToSelect('*')
                                                ->addAttributeToFilter('confirmation',$confirmationKey)
                                                ->load();
            $customerEmail = $customerCollectionFactory->getData();
            if(!empty($customerEmail)){
                foreach($customerEmail as $customerEmailAddress){
                    $customerEntittyId = $customerEmailAddress['entity_id'];
                }
                $customer = $this->customerRepository->getById($customerEntittyId);
               
                if($customer)
                {
                    $customerId = $customer->getId();
                    $emailConfirmation = $customer->getConfirmation();
                    if($emailConfirmation == $confirmationKey){
                        $customer->setConfirmation(null);
                        $this->customerRepository->save($customer);
                        //send Email to the customer
                        $mailArray['email'] = $customer->getEmail();
                        $mailArray['firstname'] = $customer->getFirstname();
                        $mailArray['lastname'] = $customer->getLastname();
                        $mailArray['name'] = $customer->getFirstname().' '.$customer->getLastname();
                        $storeScope = ScopeInterface::SCOPE_STORES;
                        $reactUrl = $this->urlHelper->getReactUrl();
                        $mailArray['store_url'] = $reactUrl;
                        $this->sendConfirmationMail($mailArray);
                        $response[] = [
                            "code" => 200,
                            "status" => true,
                            "message" => "The Email has been Verified Successfully"
                        ];
                    }
                    else{
                        $response[] = [
                            "code" => 400,
                            "status" => false,
                            "message" => "Invalid Confirmation Key"
                        ];
                    }
                } 
                else{
                    $response[] = [
                        "code" => 400,
                        "status" => false,
                        "message" => "Customer is doesn't exist with Sri Swaminathan & Co."
                    ];
                }
            }
            else{
                $response[] = [
                    "code" => 400,
                    "status" => false,
                    "message" => "Your account already confirmed."
                ];
            }
        }catch (\Exception $e) {
            $response[] = [
                "code" => 400,
                "status" => false,
                "message" => "Email Id is not registered with Sri Swaminathan & Co."

            ];
        }
        return $response;
    }

    public function createCustomerAccessToken($username,$password)
    {
        $this->validatorHelper->validate($username, $password);
        $this->getRequestThrottler()->throttle($username, RequestThrottler::USER_TYPE_CUSTOMER);
        try {
            $customerDataObject = $this->accountManagementInterface->authenticate($username, $password);
        } catch (\Exception $e) {
            $this->getRequestThrottler()->logAuthenticationFailure($username, RequestThrottler::USER_TYPE_CUSTOMER);
            throw new AuthenticationException(
                __(
                    'The account sign-in was incorrect or your account is disabled temporarily. '
                    . 'Please wait and try again later.'
                )
            );
        }
        $this->eventManager->dispatch('customer_login', ['customer' => $customerDataObject]);
        $this->getRequestThrottler()->resetAuthenticationFailuresCount($username, RequestThrottler::USER_TYPE_CUSTOMER);
        $response = $this->tokenModelFactory->create()->createCustomerToken($customerDataObject->getId())->getToken();
        return $response;
    }

    private function getRequestThrottler()
    {
        if (!$this->requestThrottler instanceof RequestThrottler) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(RequestThrottler::class);
        }
        return $this->requestThrottler;
    }

    /**
     * Login Customer
     * @param string $username
     * @param string $password
     * @return array
     */
    public function loginCustomer($username,$password)
    {
        if(!empty($username) && !empty($password)){
            $this->validatorHelper->validate($username, $password);
            try {
                $customer = $this->customerRepository->get($username);
                $requiredConfirmation = $this->scopeConfigInterface->getValue(self::ACCOUNT_CONFIRMATION_REQUIRED,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    );
                if ($customer->getConfirmation() &&  $requiredConfirmation) {
                    $response[] = ['code' => 400, 'status' => false, 'message' => "This account isn't confirmed yet. Please verify and try again."];
                    return $response;
                }
            } catch (\Exception $e) {
                $response[] = ['code' => 400, 'status' => false, 'message' => 'Invalid email or password.'];
                return $response;
            }
            try {
                $customerDataObject = $this->accountManagementInterface->authenticate($username, $password);
            } catch (\Exception $e) {
                $this->getRequestThrottler()->logAuthenticationFailure($username, RequestThrottler::USER_TYPE_CUSTOMER);
                $response[] = ['code' => 400, 'status' => false, 'token' => '', 'message' => 'The account sign-in was incorrect or your account is disabled temporarily. '
                        . 'Please wait and try again later.'];
                return $response;
            }
            $customerData = [];
            $customerId = $customer->getId();
            $customerModel = $this->customerRepository->getById($customerId);
            $customerData = $customerModel->__toArray();
            $this->eventManager->dispatch('customer_login', ['customer' => $customerDataObject]);
            $this->getRequestThrottler()->resetAuthenticationFailuresCount($username, RequestThrottler::USER_TYPE_CUSTOMER);
            $response[] = ['code' => 200, 'status' => true , 'message' => 'Logged In Successfully', 'token' =>$this->tokenModelFactory->create()->createCustomerToken($customerDataObject->getId())->getToken(), 'customer_data' => $customerData];
        }
        else{
            $response[] = ['code' => 400, 'status' => false , 'message' => 'Email or Password is Missing.'];
        }
        return $response;
    }

    public function getToken(){
        return $this->user_helper->generateResetPasswordLinkToken();
    }

    /**
     * ForgotPassword
     * @param string[] $data
     * @return array
     */
    public function forgotPassword($data)
    {
        try{
            $customer = $this->customerRepository->get($data['email']);
            
            if ($customer->getId()) {
                try {
                    $newResetPasswordLinkToken =  $this->getToken();
                    if (!is_string($newResetPasswordLinkToken) || empty($newResetPasswordLinkToken)) {
                        throw new InputException(
                            __(
                                'Invalid value of "%value" provided for the %fieldName field.',
                                ['value' => $newResetPasswordLinkToken, 'fieldName' => 'password reset token']
                            )
                        );
                    }
                    if (is_string($newResetPasswordLinkToken) && !empty($newResetPasswordLinkToken)) {
                        $customerSecure = $this->customerRegistry->retrieveSecureData($customer->getId());
                        $customerSecure->setRpToken($newResetPasswordLinkToken);
                        $gmtDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
                        $customerSecure->setRpTokenCreatedAt($gmtDate);
                        $this->customerRepository->save($customer);
                    }
                    $customerToken = $this->tokenModelFactory->create();
                    $tokenKey = $customerToken->createCustomerToken($customer->getId())->getToken();
                    $customerData = $this->collectionFactory->create()
                                                ->addAttributeToSelect('*')
                                                ->addAttributeToFilter('entity_id',$customer->getId())
                                                ->load();
                    if(isset($customerData->getData()[0])) {
                        $token = $customerData->getData()[0]['rp_token'];
                    }
                    $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
                    $reactUrl = $this->urlHelper->getReactUrl();
                    $storeCode = 1;
                    $reset_link = $reactUrl.'resetpassword?token='.$token;
                    $postData = array(
                        'first_name'=> $customer->getFirstName(),
                        'email' => $data['email'],
                        'id'   => $customer->getId(),
                        'reset_link' => $reset_link
                    );            
                    if($this->mailNotify->getModuleConfig(self::FORGOT_PASSWORD_CUSTOMER_ENABLED)){
                        $templateId = $this->mailNotify->getModuleConfig(self::FORGOT_PASSWORD_CUSTOMER_TEMPLATE);
                        $toEmail = $data['email'];
                        $fromEmail = $this->mailNotify->getModuleConfig(self::FORGOT_PASSWORD_CUSTOMER_SENDER);
                        $this->mailNotify->sendMail($toEmail,$postData,$templateId,$fromEmail);
                    }
                    $response[] =[
                        "code" => 200,
                        "status" => true,
                        "message" => "Please check your email. You will receive a reset password link in email."
                    ];
                
                } catch (\Exception $exception) {
                    $response[] =[
                        "code" => 400,
                        "status" => false,
                        "message" => $exception->getMessage()
                    ];
                }
            }
            
        } catch (\Exception $e) {
            $response[] =[
                "code" => 400,
                "status" => false,
                "message" => "Email Id is not registered with Sri Swaminathan & Co."
            ];
        }
        return $response;
    }

    /**
     * ForgotPassword
     * @param string[] $data
     * @return array
     */
    public function resetPassword($data)
    {
        if(!isset($data['password']) || empty($data['password'])){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"Please Enter Password."]; 
            return $response;
        }

         //Confirm Password Validation
        if(!isset($data['confirm_password']) || empty($data['confirm_password'])){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"Please Enter Confirm Password."]; 
            return $response;
        }

        //Validating Confirm Password as same as Password
        if(!($data['password'] == $data['confirm_password'])){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"Confirm Password should be same as Password."]; 
            return $response;
        }

        //Password Length Validation
        if(strlen($data['password']) < 6){ 
            $response[] = ["code" => 400,'success' => false, 'message' =>"Please enter minimum of 6 characters."]; 
            return $response;
        }
        try{
            $customerCollectionFactory = $this->collectionFactory->create()
                                                ->addAttributeToSelect('*')
                                                ->addAttributeToFilter('rp_token',$data['token'])
                                                ->load();
            $customerEmail = $customerCollectionFactory->getData();
            if(!empty($customerEmail)){
                if(isset($customerCollectionFactory->getData()[0])) {
                    $customerEntittyId = $customerCollectionFactory->getData()[0]['entity_id'];
                    $customerRpToken = $customerCollectionFactory->getData()[0]['rp_token'];
                }
                $customer = $this->customerRepository->getById($customerEntittyId);
                $mailArray = [
                    'firstname' => $customer->getFirstname(),
                    'lastname' =>$customer->getLastname(),
                    'emai'=> $customer->getEmail()
                ];
                // temporarily not mentioned in BRD to send the email notification on password reset.
                // $this->sendAccountActivationMail($mailArray);
                $this->setIgnoreValidationFlag($customer);
                //Validate Token and new password strength
                // $this->validateResetPasswordToken($customer->getId(), $data['token']);
                if($data['token'] ==  $customerRpToken){
                    $this->credentialsValidator->checkPasswordDifferentFromEmail(
                        $customer->getEmail(),
                        $data['password']
                    );
                    $this->checkPasswordStrength($data['password']);
                    //Update secure data
                    $customerSecure = $this->customerRegistry->retrieveSecureData($customer->getId());
                    $customerSecure->setRpToken(null);
                    $customerSecure->setRpTokenCreatedAt(null);
                    $customerSecure->setPasswordHash($this->createPasswordHash($data['password']));
                    $this->customerRepository->save($customer);
                    $response[] = [
                        'code' => 200,
                        'status' => true,
                        'message' => 'Your Password has been Reset Successfully.'
                    ];
                }
                else{
                    $response[] = [
                        'code' => 400,
                        'status' => false,
                        'message' => 'The password token is mismatched. Reset and try again.'
                    ];
                }
            }
            else{
                $response[] = [
                    'code' => 400,
                    'status' => false,
                    'message' => 'Invalid Token Or Provided Token was Expired'
                ];
            }
        } catch(\Magento\Framework\Exception\NoSuchEntityException $e)
        {
            $response[] = [
                'code' => 400,
                'status' => false,
                'message' => 'Invalid Token Or Provided Token was Expired'
            ];
        }

        return $response;
    }

    private function setIgnoreValidationFlag($customer)
    {
        $customer->setData('ignore_validation_flag', true);
    }

    protected function checkPasswordStrength($password)
    {
        $length = $this->stringHelper->strlen($password);
        $response = true;
        if ($length > self::MAX_PASSWORD_LENGTH) {
            
            $response[] = [
                'code' => 400,
                'status' => false,
                'message' => 'Please enter a password with at most '.self::MAX_PASSWORD_LENGTH.' characters.'
            ];
        }
        $configMinPasswordLength = $this->getMinPasswordLength();
        if ($length < $configMinPasswordLength) {
            
            $response[] = [
                'code' => 400,
                'status' => false,
                'message' => 'The password needs at least '. $configMinPasswordLength.' characters. Create a new password and try again.'        
            ];
        }
        if ($this->stringHelper->strlen(trim($password)) != $length) {
            
            $response[] = [
                'code' => 400,
                'status' => false,
                'message' => 'The password can\'t begin or end with a space. Verify the password and try again.'        
            ];
        }

        $requiredCharactersCheck = $this->makeRequiredCharactersCheck($password);
        if ($requiredCharactersCheck !== 0) {
           
            $response[] = [
                'code' => 400,
                'status' => false,
                'message' => 'Minimum of different classes of characters in password is '.$requiredCharactersCheck .
                         ' Classes of characters: Lower Case, Upper Case, Digits, Special Characters.'
            ];
        }
        return $response;
    }



    private function validateResetPasswordToken($customerId, $resetPasswordLinkToken)
    {
        if ($customerId !== null && $customerId <= 0) {
            throw new InputException(
                __(
                    'Invalid value of "%value" provided for the %fieldName field.',
                    ['value' => $customerId, 'fieldName' => 'customerId']
                )
            );
        }

        if ($customerId === null) {
            //Looking for the customer.
            $customerId = $this->getByToken
                ->execute($resetPasswordLinkToken)
                ->getId();
        }
        if (!is_string($resetPasswordLinkToken) || empty($resetPasswordLinkToken)) {
            $params = ['fieldName' => 'resetPasswordLinkToken'];
            throw new InputException(__('"%fieldName" is required. Enter and try again.', $params));
        }
        $customerSecureData = $this->customerRegistry->retrieveSecureData($customerId);
        $rpToken = $customerSecureData->getRpToken();
        $rpTokenCreatedAt = $customerSecureData->getRpTokenCreatedAt();
        if (!\Magento\Framework\Encryption\Helper\Security::compareStrings($rpToken, $resetPasswordLinkToken)) {
            throw new InputMismatchException(__('The password token is mismatched. Reset and try again.'));
        } elseif ($this->isResetPasswordLinkTokenExpired($rpToken, $rpTokenCreatedAt)) {
            throw new ExpiredException(__('The password token is expired. Reset and try again.'));
        }
        return true;
    }
    public function isResetPasswordLinkTokenExpired($rpToken, $rpTokenCreatedAt)
    {
        if (empty($rpToken) || empty($rpTokenCreatedAt)) {
            return true;
        }

        $expirationPeriod = $this->customerModel->getResetPasswordLinkExpirationPeriod();

        $currentTimestamp = $this->dataTimeFactory->create()->getTimestamp();
        $tokenTimestamp = $this->dataTimeFactory->create($rpTokenCreatedAt)->getTimestamp();
        if ($tokenTimestamp > $currentTimestamp) {
            return true;
        }

        $hourDifference = floor(($currentTimestamp - $tokenTimestamp) / (60 * 60));
        if ($hourDifference >= $expirationPeriod) {
            return true;
        }

        return false;
    }

    public function getGoogleSignInLink(){
        $url = SocialConstants::SOCIALLOGIN_LOGIN_URL;
        return $this->urlHelper->getBaseUrl().$url.'?provider=google';

    }

    protected function getMinPasswordLength()
    {
        return $this->scopeConfigInterface->getValue(self::XML_PATH_MINIMUM_PASSWORD_LENGTH);
    }
    
    protected function createPasswordHash($password)
    {
        return $this->encryptor->getHash($password, true);
    }
    

    protected function makeRequiredCharactersCheck($password)
    {
        $counter = 0;
        $requiredNumber = $this->scopeConfigInterface->getValue(self::XML_PATH_REQUIRED_CHARACTER_CLASSES_NUMBER);
        $return = 0;

        if (preg_match('/[0-9]+/', $password)) {
            $counter++;
        }
        if (preg_match('/[A-Z]+/', $password)) {
            $counter++;
        }
        if (preg_match('/[a-z]+/', $password)) {
            $counter++;
        }
        if (preg_match('/[^a-zA-Z0-9]+/', $password)) {
            $counter++;
        }

        if ($counter < $requiredNumber) {
            $return = $requiredNumber;
        }

        return $return;
    }

    //Email Notification on Account Registration
    public function sendNotificationMail($mailArray)
    {
        if($this->mailNotify->getModuleConfig(self::REGISTRATION_CUSTOMER_ENABLED)){
            $templateId = $this->mailNotify->getModuleConfig(self::REGISTRATION_CUSTOMER_TEMPLATE);
            $toEmail = $mailArray['email'];
            $postObject = [];
            $postObject['email'] = $mailArray['email'];
            $postObject['firstname'] = $mailArray['firstname'];
            $postObject['lastname'] = $mailArray['lastname'];
            $postObject['name'] = $mailArray['name'];
            $postObject['confirmation_link'] = $mailArray['confirmation_link'];
            $fromEmail = $this->mailNotify->getModuleConfig(self::REGISTRATION_CUSTOMER_SENDER);
            $this->mailNotify->sendMail($toEmail,$postObject,$templateId,$fromEmail);
            
        }
    } 
    
    //Email Notification on Email Confirmation
    public function sendConfirmationMail($mailArray)
    {
        if($this->mailNotify->getModuleConfig(self::CONFIRM_CUSTOMER_ENABLED)){
            $templateId = $this->mailNotify->getModuleConfig(self::CONFIRM_CUSTOMER_TEMPLATE);
            $toEmail = $mailArray['email'];
            $postObject = [];
            $postObject['email'] = $mailArray['email'];
            $postObject['firstname'] = $mailArray['firstname'];
            $postObject['lastname'] = $mailArray['lastname'];
            $postObject['name'] = $mailArray['name'];
            $postObject['support_email'] = $this->mailNotify->getModuleConfig(self::CONFIRM_CUSTOMER_SUPPORT);
            $fromEmail = $this->mailNotify->getModuleConfig(self::CONFIRM_CUSTOMER_SENDER);
            $this->mailNotify->sendMail($toEmail,$postObject,$templateId,$fromEmail);
        }
    } 

    //Email Notification of Password reset / Account Activation. / currently not mentioned in BRD.
    // public function sendAccountActivationMail($mailArray)
    // {
    //      // try { 
            //     $mailArray['store_id'] = $customer['store_id'];
            //     $mailArray['email'] = $customer['email'];
            //     $mailArray['firstname'] = $customer['firstname'];
            //     $mailArray['lastname'] = $customer['lastname'];
            //     $mailArray['name'] = $customer['firstname']. ' ' . $customer['lastname'];
            //     $storeScope = ScopeInterface::SCOPE_STORES;
            //     $baseUrl = $this->scopeConfigInterface->getValue(self::REACT_URL, $storeScope);
            //     // $storeData = $this->storeManager->getStore($customer['store_id']);
            //     // $storeCode = (string)$storeData->getCode();
            //     $mailArray['store_url'] = $baseUrl;
            //     $mailArray['confirmation_link'] = $baseUrl.'confirm/email/'.$customerNew->getConfirmation();
               
            //     $this->sendNotificationMail($mailArray);
            // } catch (\Exception $e) {
            //     $this->logger->info($e->getMessage());
            // }
    // }
}
 