<?php

namespace Comfino\ComfinoGateway\Model\ResourceModel\ComfinoApplication;

use Comfino\ComfinoGateway\Model\ComfinoApplication;
use Comfino\ComfinoGateway\Model\ResourceModel\ComfinoApplication as ComfinoResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init(ComfinoApplication::class, ComfinoResource::class);
    }
}
