<?php
namespace Swaminathan\CmsPlpPdp\Api;

/**
 * Interface SearchInterface
 *
 * @api
 */
interface SearchInterface
{

    /**
     * Returns a list of the searched results.
     * 
     * @param mixed $data
     * @return array
     */
    public function getSearchResult($data);

    /**
     * Returns a list of the searched suggestion.
     * 
     * @param mixed $data
     * @return array
     */
    public function getSearchSuggestion($data);

}