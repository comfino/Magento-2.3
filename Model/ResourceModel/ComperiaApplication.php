<?php

namespace Comperia\ComperiaGateway\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ComperiaApplication extends AbstractDb
{
    public function _construct()
    {
        $this->_init('comperia_application', 'id');
    }
}
