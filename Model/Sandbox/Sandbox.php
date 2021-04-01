<?php

namespace Comperia\ComperiaGateway\Model\Sandbox;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Sandbox
 *
 * @package Comperia\ComperiaGateway\Model\Sandbox
 */
class Sandbox implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $arr = $this->toArray();
        $ret = [];
        foreach ($arr as $key => $value) {
            $ret[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $choose = [
            '0' => 'Wyłączony',
            '1' => 'Włączony',
        ];

        return $choose;
    }
}
