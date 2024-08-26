<?php
namespace Swaminathan\Cart\Api;

interface MergeCartInterface
{
    /**
     * Merge Cart Items
     *
     * @param mixed
     * @return string
     */
    public function mergeCart($param);

}
