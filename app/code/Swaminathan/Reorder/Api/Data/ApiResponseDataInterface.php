<?php

namespace Swaminathan\Reorder\Api\Data;

interface ApiResponseDataInterface
{
    const MESSAGE     = 'message';
    const STATUS      = 'status';

     /**
     * get Message
     *
     * @param string $message
     * @return $this
     */
    public function getMessage();
     /**
     * get Status
     *
     * @param string $status
     * @return $this
     */
    public function getStatus();

    /**
    * set  Message
    *
    * @param string $message
    * @return $this
    */
    public function setMessage($message);
    /**
    * set  status
    *
    * @param string $status
    * @return $this
    */
    public function setStatus($status);
}