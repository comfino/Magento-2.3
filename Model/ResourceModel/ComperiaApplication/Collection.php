<?php

namespace Comperia\ComperiaGateway\Model\ResourceModel\ComperiaApplication;

use Comperia\ComperiaGateway\Model\ComperiaApplication;
use Comperia\ComperiaGateway\Model\ResourceModel\ComperiaApplication as ComperiaResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init(ComperiaApplication::class, ComperiaResource::class);
    }
}
