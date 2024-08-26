<?php
namespace Swaminathan\SocialLogin\Api;

interface SocialLoginInterface
{
    /**
     * Social Login
     *
     * @param mixed $data
     * @return string
     */
    public function socialLogin($data);

}
