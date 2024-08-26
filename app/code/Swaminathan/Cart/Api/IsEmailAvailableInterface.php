<?php
namespace Swaminathan\Cart\Api;

interface IsEmailAvailableInterface
{
    /**
     * @param string $customerEmail
     * @return string
     */
 
     public function emailExistOrNot($customerEmail);

}
