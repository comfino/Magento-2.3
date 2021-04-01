<?php

namespace Comperia\ComperiaGateway\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class ComperiaApplication
 *
 * @package Comperia\ComperiaGateway\Model
 */
class ComperiaApplication extends AbstractModel
{
    public function _construct()
    {
        $this->_init('Comperia\ComperiaGateway\Model\ResourceModel\ComperiaApplication');
    }
}
