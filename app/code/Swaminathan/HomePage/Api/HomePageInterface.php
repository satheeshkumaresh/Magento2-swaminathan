<?php
namespace Swaminathan\HomePage\Api;


/**
 * Interface MaintenanceInterface
 *
 * @api
 */
interface HomePageInterface
{
    /**
     * @return string[]
     */
    public function getHome();

    /**
     * @return string[]
     */
    public function getHeaderFooterContent();
    
}