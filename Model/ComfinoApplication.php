<?php

namespace Comfino\ComfinoGateway\Model;

use Magento\Framework\Model\AbstractModel;
use Comfino\ComfinoGateway\Model\ResourceModel\ComfinoApplication as ComfinoApplicationResource;

class ComfinoApplication extends AbstractModel
{
    public function _construct()
    {
        $this->_init(ComfinoApplicationResource::class);
    }
}
