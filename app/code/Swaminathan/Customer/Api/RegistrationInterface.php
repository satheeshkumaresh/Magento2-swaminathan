<?php
namespace Swaminathan\Customer\Api;

/**
 * Interface RegistrationInterface
 *
 * @api
 */
interface RegistrationInterface
{
    /**
     * Create Swaminathan Customer
     * 
     * @param string[] $customer
     * @return string
     */
    public function createCustomer($customer);

    /**
     * Send Acccount Confirmation Email 
     * @param string $email
     * @return array
     */
    public function sendAccountConfirmationEmail($email);

     /**
     * Login Customer
     * @param string $username
     * @param string $password
     * @return array
     */
    public function loginCustomer($username,$password);

     /**
     * ForgotPassword
     * @param string[] $data
     * @return array
     */
    public function forgotPassword($data);

    /**
     * Confirm Swaminathan Customer
     * @param string $confirmationKey
     * @return array
     */
    public function confirmEmail($confirmationKey);

       /**
     * ResetPassword
     * @param string[] $data
     * @return array
     */
    public function resetPassword($data);

    /**
     * Google Sign In Link 
     * @return string
    */
    public function getGoogleSignInLink();


}