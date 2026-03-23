<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace ComfinoExternal\Cache\Adapter\Common;

trait TagSupportWithArray
{
    /**
     * @param string $name
     * @return mixed
     */
    abstract public function getDirectValue($name);
    /**
     * @param string $name
     * @param mixed $value
     */
    abstract public function setDirectValue($name, $value);
    
    protected function appendListItem($name, $value)
    {
        $data = $this->getDirectValue($name);
        if (!is_array($data)) {
            $data = [];
        }
        $data[] = $value;
        $this->setDirectValue($name, $data);
    }
    
    protected function getList($name)
    {
        $data = $this->getDirectValue($name);
        if (!is_array($data)) {
            $data = [];
        }
        return $data;
    }
    
    protected function removeList($name)
    {
        $this->setDirectValue($name, []);
        return \true;
    }
    
    protected function removeListItem($name, $key)
    {
        $data = $this->getList($name);
        foreach ($data as $i => $value) {
            if ($key === $value) {
                unset($data[$i]);
            }
        }
        return $this->setDirectValue($name, $data);
    }
}
