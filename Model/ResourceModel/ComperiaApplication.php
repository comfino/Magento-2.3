<?php

namespace Comperia\ComperiaGateway\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class ComperiaApplication
 *
 * @package Comperia\ComperiaGateway\Model\ResourceModel
 */
class ComperiaApplication extends AbstractDb
{
    public function _construct()
    {
        $this->_init('comperia_application', 'id');
    }
}
