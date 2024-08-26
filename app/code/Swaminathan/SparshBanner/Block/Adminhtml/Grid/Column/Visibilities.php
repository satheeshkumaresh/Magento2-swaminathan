<?php
namespace Swaminathan\SparshBanner\Block\Adminhtml\Grid\Column;

class Visibilities extends \Magento\Backend\Block\Widget\Grid\Column
{
    /**
     * Add to column decorated status
     *
     * @return array
     */
    public function getFrameCallback()
    {
        return [$this, 'decorateStatus'];
    }

    /**
     * Decorate status column values
     *
     * @param string                                 $value value
     * @param \Magento\Framework\Model\AbstractModel $row   row
     *
     * @return string
     */
    public function decorateStatus($value, $row)
    {
        if ($row->getIsActive() || $row->getStatus()) {
            $cell = $value ;
        } else {
            $cell = $value;
        }
        return $cell;
    }
}
