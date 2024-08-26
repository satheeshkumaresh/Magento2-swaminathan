<?php 
namespace Swaminathan\LayeredFilter\Api;
 
 
interface FilterInterface {

	/**
	 *  @param int $category_id
     * @return array
	 * 
     */
	
    public function retrieve($category_id);

}
