<?php
namespace Swaminathan\Reorder\Model;
use Swaminathan\Reorder\Api\Data\ApiResponseDataInterface;

class ApiResponseMessage extends \Magento\Framework\Model\AbstractExtensibleModel implements  ApiResponseDataInterface
{
    public function getMessage()
    {
       return $this->_getData(self::STATUS);
    }

    public function getStatus()
    {
        return $this->_getData(self::MESSAGE);
    }
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
}