<?php

namespace Swaminathan\NewsletterSubscription\Api\Data;

use Magento\Cms\Api\Data\PageSearchResultsInterface;

interface SubscribersSearchResultsInterface {

    /**
     * Get subscribers list.
     *
     * @return \Swaminathan\NewsletterSubscription\Api\Data\SubscriberInterface[]
     */
    public function getItems();

    /**
     * Set subscribers list.
     *
     * @param \Swaminathan\NewsletterSubscription\Api\Data\SubscriberInterface[] $subscribers
     * @return $this
     */
    public function setItems(array $subscribers);
}