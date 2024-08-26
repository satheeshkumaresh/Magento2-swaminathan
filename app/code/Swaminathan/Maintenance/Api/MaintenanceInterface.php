<?php 
namespace Swaminathan\Maintenance\Api;
 

interface  MaintenanceInterface 
{
    /**
     *
     * @api
     * @return bool
     * 
     */
    public function maintenance();
}