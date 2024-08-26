<?php
/**
 * A Magento 2 module named Swaminathan\NewsletterSubscription
 * Copyright (C) 2018  
 */

namespace Swaminathan\NewsletterSubscription\Api;

interface CustomerNewsLetterSubscriptionInterface
{

    /**
     * POST for newsletter api
     * @param int $customerId
     * @param string $isSubscriberStatus
     * @return string
     */

    public function postNewsLetter($customerId,$isSubscriberStatus);


}