<?php

namespace Comperia\ComperiaGateway\Model;

use Magento\Framework\Model\AbstractModel;
use Comperia\ComperiaGateway\Model\ResourceModel\ComperiaApplication as ComperiaApplicationResource;

class ComperiaApplication extends AbstractModel
{
    public function _construct()
    {
        $this->_init(ComperiaApplicationResource::class);
    }
}
