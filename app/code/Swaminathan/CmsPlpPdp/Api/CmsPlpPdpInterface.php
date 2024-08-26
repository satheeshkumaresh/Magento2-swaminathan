<?php
namespace Swaminathan\CmsPlpPdp\Api;

/**
 * Interface CmsPlpPdpInterface
 *
 * @api
 */
interface CmsPlpPdpInterface
{
    /**
     * Returns a list of the filtered products, category or cms page.
     * 
     * @param mixed $data
     * @return array
     */
    public function getContent($data);

}