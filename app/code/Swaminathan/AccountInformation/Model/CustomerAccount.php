<?php
namespace Swaminathan\AccountInformation\Model;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Request\Http;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Newsletter\Model\SubscriberFactory;
class CustomerAccount
{
    public function __construct(
        CustomerFactory $customerFactory,
        Customer $customer,
        EncryptorInterface $encryptor,
        Http $http,
        TokenFactory $tokenFactory,
        EmailValidator $emailValidator,
        SubscriberFactory $subscriberFactory
    ) {
        $this->customerModel = $customer;
        $this->customerFactory = $customerFactory;
        $this->encryptor = $encryptor;
        $this->http = $http;
        $this->tokenFactory = $tokenFactory;
        $this->emailValidator = $emailValidator;
        $this->subscriberFactory = $subscriberFactory;
    }
    public function validateMobile($mobile)
    {
        return preg_match('/^[0-9]{10}+$/', $mobile);
    }
    public function validatePassword($current_password)
    {
        // Validate password strength
        $pass = [];
        $data["uc"] = preg_match("@[A-Z]@", $current_password);
        $data["lc"] = preg_match("@[a-z]@", $current_password);
        $data["sp"] = preg_match("@[0-9]@", $current_password);
        $data["count"] = preg_match("@[^\w]@", $current_password);
        $pass = $data;
        return $pass;
    }
    public function validateNewPassword($new_password)
    {
        // Validate password strength
        $pass = [];
        $data["uc"] = preg_match("@[A-Z]@", $new_password);
        $data["lc"] = preg_match("@[a-z]@", $new_password);
        $data["sp"] = preg_match("@[0-9]@", $new_password);
        $data["count"] = preg_match("@[^\w]@", $new_password);

        $pass = $data;
        return $pass;
    }
    public function getCustomerId()
    {
        $authorizationHeader = $this->http->getHeader("Authorization");
        $tokenParts = explode("Bearer", $authorizationHeader);
        $tokenPayload = trim(array_pop($tokenParts));
        /** @var Token $token */
        $token = $this->tokenFactory->create();
        $token->loadByToken($tokenPayload);
        $customerId = $token->getCustomerId();
        return $customerId;
    }
    /**
     * @param string[] $data
     * @return array
     */
    public function saveCustomerAccount($data)
    {
        $response = [];
        if(!isset($data['firstname']) || $data['firstname'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the First Name",
            ];
            return $response;
        }
        if(!isset($data['lastname']) || $data['lastname'] == ""){
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Last Name",
            ];
            return $response;
        }
        if(isset($data['email']) && $data['email'] != ""){
            $validateEmail = $this->emailValidator->isValid($data["email"]);
            if ($validateEmail == false) {
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Enter Valid Email Address",
                ];
                return $response;
            }
        }
        if (isset($data["new_password"])) {
            if($data["new_password"] != ""){
                $validatePassword = $this->validateNewPassword($data["new_password"]);
                if (
                    $validatePassword["uc"] == 0 ||
                    $validatePassword["lc"] == 0 ||
                    $validatePassword["sp"] == 0 ||
                    $validatePassword["count"] == 0 ||
                    strlen($data["new_password"]) < 8
                ) {
                    $response[] = [
                        "code" => 400,
                        "success" => false,
                        "message" => "Please provide valid new password.",
                    ];
                    return $response;
                }
            }
        }

        //Validating Confirm Password as same as Password
        else if (!($data["new_password"] == $data["confirm_password"])) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Confirm Password should be same as Password.",
            ];
            return $response;
        }
        $isExist = 0;
        $registeredEmail = [];
        $message = false;
        $customerId = $this->getCustomerId();
        $customer = $this->customerFactory->create();
        $customer->load($customerId);
        $customerCollections = $this->customerFactory->create()->getCollection();
        foreach($customerCollections->getData() as $customerDatas){
            $registeredEmail[] = $customerDatas['email'];
        }
        if(isset($data['email']) && $data["current_password"] != ""){
            if (in_array($data['email'], $registeredEmail)){
                if($customer->getData()['email'] != $data['email']){
                    $isExist++;
                }
            }
        }
        if($isExist == 0){
            $currentstorepassword = $customer->getPasswordHash();
            $customer->setFirstname($data["firstname"]);
            $customer->setLastname($data["lastname"]);
            if (isset($data["mobile"])) {
                $customer->setMobile($data["mobile"]);
            }
            if (!empty($data["current_password"]) || !empty($data["email"])) {
                if ($this->encryptor->validateHash($data["current_password"],$currentstorepassword)){
                    if (!empty($data["email"])) {
                        $customer->setEmail($data['email']);
                    }
                    if (!empty($data["new_password"])) {
                        $customer->setPasswordHash(  $this->encryptor->getHash($data["new_password"], true)
                        );
                    }
                    $message = true;
                }
                else if($data["current_password"] == ""){
                    $response[] = [
                        "code" => 400,
                        "success" => false,
                        "message" => "Please enter current password",
                    ];
                    return $response;
                } 
                else {
                    $response[] = [
                        "code" => 400,
                        "success" => false,
                        "message" => "Invalid current password",
                    ];
                    return $response;
                }
            }
            $save = $customer->save();
            if($save){
                $response[] = [
                    "code" => 200,
                    "success" => true,
                    "message" => "Account information updated Successfully.",
                    "isLogout" => $message
                ];
            }
            else{
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Error Saving Data.",
                ];
            }
        }
        else{
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "A customer with the same email address already exists in an associated website.",
            ];
        }
        return $response;
    }
    /**
     *
     * @return array
     */
    public function getCustomerInformation()
    {
        $customerId = $this->getCustomerId();
        $customerCollection = $this->customerModel
                              ->getCollection()
                              ->addFieldToFilter("entity_id", $customerId);
        if (count($customerCollection) == 1) {
            $customerData = [];
            $customer = $this->customerFactory->create();
            $customer->load($customerId);
            $data["firstname"] = $customer->getFirstname();
            $data["lastname"] = $customer->getLastname();
            $data["mobile"] = $customer->getMobile();
            $data["email"] = $customer->getEmail();
            $status = $this->subscriberFactory->create()->loadByCustomerId($customerId);
            $subScriber = $status->getSubscriberStatus();
            if( $subScriber ==1 ){
                $data['is_subscriber'] = true;
            }else{
                $data['is_subscriber'] = false;
            }
            $customerData[]= $data;
            $response[] = [
                "code" => 200,
                "success" => true,
                "data" => $customerData,
            ];
        } else {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Invalid customerId",
            ];
        }

        return $response;
    }
}
