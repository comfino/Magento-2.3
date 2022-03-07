<?php

namespace Comfino\ComfinoGateway\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ComfinoApplication extends AbstractDb
{
    public function _construct()
    {
        $this->_init('comfino_application', 'id');
    }
}
