<?php
namespace Swaminathan\Maintenance\Model;


class Maintenance implements \Swaminathan\Maintenance\Api\MaintenanceInterface
{
        public function maintenance()
    {
        
            $response[] = ["code" => 200,'success' => true, 'message' =>"Success"];
            return $response;
        
    }

}