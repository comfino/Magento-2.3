<?php

namespace Comperia\ComperiaGateway\Model\ResourceModel\ComperiaApplication;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package Comperia\ComperiaGateway\Model\ResourceModel\ComperiaApplication
 */
class Collection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init('Comperia\ComperiaGateway\Model\ComperiaApplication', 'Comperia\ComperiaGateway\Model\ResourceModel\ComperiaApplication');
    }
}
