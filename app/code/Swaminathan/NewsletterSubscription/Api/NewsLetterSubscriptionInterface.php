<?php
/**
 * A Magento 2 module named Swaminathan\NewsletterSubscription
 * Copyright (C) 2018  
 */

namespace Swaminathan\NewsletterSubscription\Api;

interface NewsLetterSubscriptionInterface
{

    /**
     * POST for newsletter api
     * @param int $customerId
     * @return string
     */

    public function postNewsLetter($customerId);


}