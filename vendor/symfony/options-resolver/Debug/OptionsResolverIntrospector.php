<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Symfony\Component\OptionsResolver\Debug;

use ComfinoExternal\Symfony\Component\OptionsResolver\Exception\NoConfigurationException;
use ComfinoExternal\Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use ComfinoExternal\Symfony\Component\OptionsResolver\OptionsResolver;

class OptionsResolverIntrospector
{
    private $get;
    public function __construct(OptionsResolver $optionsResolver)
    {
        $this->get = \Closure::bind(function ($property, $option, $message) {
            if (!$this->isDefined($option)) {
                throw new UndefinedOptionsException(sprintf('The option "%s" does not exist.', $option));
            }
            if (!\array_key_exists($option, $this->{$property})) {
                throw new NoConfigurationException($message);
            }
            return $this->{$property}[$option];
        }, $optionsResolver, $optionsResolver);
    }
    /**
     * @return mixed
     * @throws NoConfigurationException
     */
    public function getDefault(string $option)
    {
        return ($this->get)('defaults', $option, sprintf('No default value was set for the "%s" option.', $option));
    }
    /**
     * @return \Closure[]
     * @throws NoConfigurationException
     */
    public function getLazyClosures(string $option): array
    {
        return ($this->get)('lazy', $option, sprintf('No lazy closures were set for the "%s" option.', $option));
    }
    /**
     * @return string[]
     * @throws NoConfigurationException
     */
    public function getAllowedTypes(string $option): array
    {
        return ($this->get)('allowedTypes', $option, sprintf('No allowed types were set for the "%s" option.', $option));
    }
    /**
     * @return mixed[]
     * @throws NoConfigurationException
     */
    public function getAllowedValues(string $option): array
    {
        return ($this->get)('allowedValues', $option, sprintf('No allowed values were set for the "%s" option.', $option));
    }
    /**
     * @throws NoConfigurationException
     */
    public function getNormalizer(string $option): \Closure
    {
        return current($this->getNormalizers($option));
    }
    /**
     * @throws NoConfigurationException
     */
    public function getNormalizers(string $option): array
    {
        return ($this->get)('normalizers', $option, sprintf('No normalizer was set for the "%s" option.', $option));
    }
    /**
     * @return string|\Closure
     * @throws NoConfigurationException
     */
    public function getDeprecationMessage(string $option)
    {
        return ($this->get)('deprecated', $option, sprintf('No deprecation was set for the "%s" option.', $option));
    }
}
