<?php 
namespace Swaminathan\AccountInformation\Api;
 
 
interface MyOrderInterface {
    /**
     * 
     * 
     * @return array
     */
    public function getCustomerId();
    
    /**
     * @param  mixed $pageSize
     * @param  int $currPage
     * @return array
     */

    public function getMyOrderDetail($pageSize,$currPage);
     /**
     * 
     * 
     * @return array
     */

    public function getRecentOrderDetail();

}
