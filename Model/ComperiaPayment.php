<?php

namespace Comperia\ComperiaGateway\Model;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class ComperiaPayment
 *
 * @package Comperia\ComperiaGateway\Model
 */
class ComperiaPayment extends AbstractMethod
{
    /**
     * @var string
     */
    protected $_code = 'comperiapayment';
}
