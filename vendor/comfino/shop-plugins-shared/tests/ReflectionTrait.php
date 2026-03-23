<?php

declare(strict_types=1);

namespace Comfino\Tests;

trait ReflectionTrait
{
    /**
     * @param object $object
     * @param string $name
     * @return mixed
     */
    public function getConstantFromObject($object, $name)
    {
        return (new \ReflectionObject($object))->getConstant($name);
    }

    /**
     * @throws \ReflectionException
     * @param string $class
     * @param string $name
     * @return mixed
     */
    public function getConstantFromClass($class, $name)
    {
        return (new \ReflectionClass($class))->getConstant($name);
    }

    /**
     * @throws \ReflectionException
     * @param object $object
     * @param string $propertyName
     * @return mixed
     */
    public function getPropertyValue($object, $propertyName)
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @throws \ReflectionException
     * @param object $object
     * @param string $methodName
     * @param mixed[] $arguments
     * @return mixed
     */
    public function getMethodResult($object, $methodName, $arguments = [])
    {
        $reflection = new \ReflectionObject($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $arguments);
    }
}
