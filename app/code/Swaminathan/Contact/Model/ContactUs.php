<?php
namespace Swaminathan\Contact\Model;

use Swaminathan\Contact\Api\ContactInterface;
use Swaminathan\Contact\Model\ContactFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Swaminathan\Contact\Helper\AdminNotify;
class ContactUs implements ContactInterface
{
    const OPEN = 1;
    protected $contactFactory;
    protected $timezoneInterface;
    protected $notifyHelper;
    public function __construct(
        ContactFactory $contactFactory,
        TimezoneInterface $timezoneInterface,
        AdminNotify $notifyHelper
    ) {
        $this->contactFactory = $contactFactory;  
        $this->timezoneInterface = $timezoneInterface;
        $this->notifyHelper = $notifyHelper;
    }
     /**
     * {@inheritdoc}
     */
    public function setData($data)
    {   
        $response = [];
        $todayDateTime = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        $customer_id = $data['customer_id'];
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $message = $data['message'];
        
        if((isset($customer_id)) && isset($name) != "" && isset($email) != "" && isset($phone) != "" && $message != ""){ 
            // validate Email Address  
            if (!\Zend_Validate::is(trim($email), 'EmailAddress')) {
                $response[] = [
                    "code" => 200,
                    "status" => true,
                    "message" => "Please provide valid Email Address."
                ];
                return $response;
            }
            $input = [
                'customer_id' => $customer_id, 
                'name' => $name,                           
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'status' => self::OPEN,
                'createdon' => $todayDateTime
            ];  
            $postData = $this->contactFactory->create();
            $save = $postData->addData($input)->save();
            if($save){
                $mail['name'] = $name;
                $mail['email'] = $email;
                $mail['phone'] = $phone;
                $mail['message'] = $message;
                $test1 = $this->notifyHelper->emailNotifyAdmin($mail); 
                $test2 = $this->notifyHelper->emailAcknowledgeCustomer($mail);  
                $response[] = [
                    "code" => 200,
                    "status" => true,
                    "message" => "Thanks for contacting us.We'll get back to you as soon as possible."
                ];
            }
            else{
                $response[] = [
                    "code" => 200,
                    "status" => true,
                    "message" => "Error saving data."
                ];
            }
        }
        else{
            $response[] = [
                "code" => 200,
                "status" => true,
                "message" => "Parameter Missing."
            ];
        }
        return $response;
    }
    public function validate_mobile($mobile)
    {
        return preg_match('/^[0-9]{10}+$/', $mobile);
    }
}
