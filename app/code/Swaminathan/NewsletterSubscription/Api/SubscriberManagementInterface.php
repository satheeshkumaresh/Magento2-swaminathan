<?php
/**
 * Copyright © Albedo All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Swaminathan\NewsletterSubscription\Api;

interface SubscriberManagementInterface
{

    /**
     * POST for Subscriber api
     * @param \Swaminathan\NewsletterSubscription\Api\Data\SubscriberInterface $subscriber
     * @return \Swaminathan\NewsletterSubscription\Api\Data\SubscriberInterface
     */
    public function postSubscriber($subscriber);

    /**
     * @param int $id
     * @param string $confirmationCode
     * @return \Swaminathan\NewsletterSubscription\Api\Data\SubscriberInterface
     */
    public function postUnsubscribe($id,$confirmationCode);

    /**
     * @param int $id
     * @param string $confirmationCode
     * @return \Swaminathan\NewsletterSubscription\Api\Data\SubscriberInterface
     */
    public function postConfirm($id,$confirmationCode);

    /**
     * Retrieve pages matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Swaminathan\NewsletterSubscription\Api\Data\SubscribersSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}


