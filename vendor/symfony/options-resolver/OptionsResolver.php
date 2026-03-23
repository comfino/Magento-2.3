<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Symfony\Component\OptionsResolver;

use ComfinoExternal\Symfony\Component\OptionsResolver\Exception\AccessException;
use ComfinoExternal\Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use ComfinoExternal\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use ComfinoExternal\Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use ComfinoExternal\Symfony\Component\OptionsResolver\Exception\NoSuchOptionException;
use ComfinoExternal\Symfony\Component\OptionsResolver\Exception\OptionDefinitionException;
use ComfinoExternal\Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class OptionsResolver implements Options
{
    private $defined = [];
    
    private $defaults = [];
    /**
     * @var \Closure[][]
     */
    private $nested = [];
    
    private $required = [];
    
    private $resolved = [];
    /**
     * @var \Closure[][]
     */
    private $normalizers = [];
    
    private $allowedValues = [];
    
    private $allowedTypes = [];
    
    private $lazy = [];
    
    private $calling = [];
    
    private $deprecated = [];
    
    private $given = [];
    
    private $locked = \false;
    private $parentsOptions = [];
    private const TYPE_ALIASES = ['boolean' => 'bool', 'integer' => 'int', 'double' => 'float'];
    /**
     * @param string $option
     * @param mixed $value
     * @return $this
     * @throws AccessException
     */
    public function setDefault($option, $value)
    {
        if ($this->locked) {
            throw new AccessException('Default values cannot be set from a lazy option or normalizer.');
        }

        if ($value instanceof \Closure) {
            $reflClosure = new \ReflectionFunction($value);
            $params = $reflClosure->getParameters();
            if (isset($params[0]) && Options::class === $this->getParameterClassName($params[0])) {
                if (!isset($this->defaults[$option])) {
                    $this->defaults[$option] = null;
                }
                
                if (!isset($this->lazy[$option]) || !isset($params[1])) {
                    $this->lazy[$option] = [];
                }
                
                $this->lazy[$option][] = $value;
                $this->defined[$option] = \true;
                
                unset($this->resolved[$option], $this->nested[$option]);
                return $this;
            }
            if (isset($params[0]) && null !== ($type = $params[0]->getType()) && self::class === $type->getName() && (!isset($params[1]) || ($type = $params[1]->getType()) instanceof \ReflectionNamedType && Options::class === $type->getName())) {
                $this->nested[$option][] = $value;
                $this->defaults[$option] = [];
                $this->defined[$option] = \true;
                
                unset($this->resolved[$option], $this->lazy[$option]);
                return $this;
            }
        }
        
        unset($this->lazy[$option], $this->nested[$option]);

        if (!isset($this->defined[$option]) || \array_key_exists($option, $this->resolved)) {
            $this->resolved[$option] = $value;
        }
        $this->defaults[$option] = $value;
        $this->defined[$option] = \true;
        return $this;
    }
    /**
     * @return $this
     * @throws AccessException
     */
    public function setDefaults(array $defaults)
    {
        foreach ($defaults as $option => $value) {
            $this->setDefault($option, $value);
        }
        return $this;
    }
    /**
     * @param string $option
     * @return bool
     */
    public function hasDefault($option)
    {
        return \array_key_exists($option, $this->defaults);
    }
    /**
     * @param string|string[] $optionNames
     * @return $this
     * @throws AccessException
     */
    public function setRequired($optionNames)
    {
        if ($this->locked) {
            throw new AccessException('Options cannot be made required from a lazy option or normalizer.');
        }
        foreach ((array) $optionNames as $option) {
            $this->defined[$option] = \true;
            $this->required[$option] = \true;
        }
        return $this;
    }
    /**
     * @param string $option
     * @return bool
     */
    public function isRequired($option)
    {
        return isset($this->required[$option]);
    }
    /**
     * @return string[]
     */
    public function getRequiredOptions()
    {
        return array_keys($this->required);
    }
    /**
     * @param string $option
     * @return bool
     */
    public function isMissing($option)
    {
        return isset($this->required[$option]) && !\array_key_exists($option, $this->defaults);
    }
    /**
     * @return string[]
     */
    public function getMissingOptions()
    {
        return array_keys(array_diff_key($this->required, $this->defaults));
    }
    /**
     * @param string|string[] $optionNames
     * @return $this
     * @throws AccessException
     */
    public function setDefined($optionNames)
    {
        if ($this->locked) {
            throw new AccessException('Options cannot be defined from a lazy option or normalizer.');
        }
        foreach ((array) $optionNames as $option) {
            $this->defined[$option] = \true;
        }
        return $this;
    }
    /**
     * @param string $option
     * @return bool
     */
    public function isDefined($option)
    {
        return isset($this->defined[$option]);
    }
    /**
     * @return string[]
     */
    public function getDefinedOptions()
    {
        return array_keys($this->defined);
    }
    public function isNested(string $option): bool
    {
        return isset($this->nested[$option]);
    }
    /**
     * @param string|\Closure $deprecationMessage
     */
    public function setDeprecated(string $option, $deprecationMessage = 'The option "%name%" is deprecated.'): self
    {
        if ($this->locked) {
            throw new AccessException('Options cannot be deprecated from a lazy option or normalizer.');
        }
        if (!isset($this->defined[$option])) {
            throw new UndefinedOptionsException(sprintf('The option "%s" does not exist, defined options are: "%s".', $this->formatOptions([$option]), implode('", "', array_keys($this->defined))));
        }
        if (!\is_string($deprecationMessage) && !$deprecationMessage instanceof \Closure) {
            throw new InvalidArgumentException(sprintf('Invalid type for deprecation message argument, expected string or \Closure, but got "%s".', \gettype($deprecationMessage)));
        }
        
        if ('' === $deprecationMessage) {
            return $this;
        }
        $this->deprecated[$option] = $deprecationMessage;
        
        unset($this->resolved[$option]);
        return $this;
    }
    public function isDeprecated(string $option): bool
    {
        return isset($this->deprecated[$option]);
    }
    /**
     * @param string $option
     * @return $this
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    public function setNormalizer($option, \Closure $normalizer)
    {
        if ($this->locked) {
            throw new AccessException('Normalizers cannot be set from a lazy option or normalizer.');
        }
        if (!isset($this->defined[$option])) {
            throw new UndefinedOptionsException(sprintf('The option "%s" does not exist. Defined options are: "%s".', $this->formatOptions([$option]), implode('", "', array_keys($this->defined))));
        }
        $this->normalizers[$option] = [$normalizer];
        
        unset($this->resolved[$option]);
        return $this;
    }
    /**
     * @return $this
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    public function addNormalizer(string $option, \Closure $normalizer, bool $forcePrepend = \false): self
    {
        if ($this->locked) {
            throw new AccessException('Normalizers cannot be set from a lazy option or normalizer.');
        }
        if (!isset($this->defined[$option])) {
            throw new UndefinedOptionsException(sprintf('The option "%s" does not exist. Defined options are: "%s".', $this->formatOptions([$option]), implode('", "', array_keys($this->defined))));
        }
        if ($forcePrepend) {
            $this->normalizers[$option] = $this->normalizers[$option] ?? [];
            array_unshift($this->normalizers[$option], $normalizer);
        } else {
            $this->normalizers[$option][] = $normalizer;
        }
        
        unset($this->resolved[$option]);
        return $this;
    }
    /**
     * @param string $option
     * @param mixed $allowedValues
     * @return $this
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    public function setAllowedValues($option, $allowedValues)
    {
        if ($this->locked) {
            throw new AccessException('Allowed values cannot be set from a lazy option or normalizer.');
        }
        if (!isset($this->defined[$option])) {
            throw new UndefinedOptionsException(sprintf('The option "%s" does not exist. Defined options are: "%s".', $this->formatOptions([$option]), implode('", "', array_keys($this->defined))));
        }
        $this->allowedValues[$option] = \is_array($allowedValues) ? $allowedValues : [$allowedValues];
        
        unset($this->resolved[$option]);
        return $this;
    }
    /**
     * @param string $option
     * @param mixed $allowedValues
     * @return $this
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    public function addAllowedValues($option, $allowedValues)
    {
        if ($this->locked) {
            throw new AccessException('Allowed values cannot be added from a lazy option or normalizer.');
        }
        if (!isset($this->defined[$option])) {
            throw new UndefinedOptionsException(sprintf('The option "%s" does not exist. Defined options are: "%s".', $this->formatOptions([$option]), implode('", "', array_keys($this->defined))));
        }
        if (!\is_array($allowedValues)) {
            $allowedValues = [$allowedValues];
        }
        if (!isset($this->allowedValues[$option])) {
            $this->allowedValues[$option] = $allowedValues;
        } else {
            $this->allowedValues[$option] = array_merge($this->allowedValues[$option], $allowedValues);
        }
        
        unset($this->resolved[$option]);
        return $this;
    }
    /**
     * @param string $option
     * @param string|string[] $allowedTypes
     * @return $this
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    public function setAllowedTypes($option, $allowedTypes)
    {
        if ($this->locked) {
            throw new AccessException('Allowed types cannot be set from a lazy option or normalizer.');
        }
        if (!isset($this->defined[$option])) {
            throw new UndefinedOptionsException(sprintf('The option "%s" does not exist. Defined options are: "%s".', $this->formatOptions([$option]), implode('", "', array_keys($this->defined))));
        }
        $this->allowedTypes[$option] = (array) $allowedTypes;
        
        unset($this->resolved[$option]);
        return $this;
    }
    /**
     * @param string $option
     * @param string|string[] $allowedTypes
     * @return $this
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    public function addAllowedTypes($option, $allowedTypes)
    {
        if ($this->locked) {
            throw new AccessException('Allowed types cannot be added from a lazy option or normalizer.');
        }
        if (!isset($this->defined[$option])) {
            throw new UndefinedOptionsException(sprintf('The option "%s" does not exist. Defined options are: "%s".', $this->formatOptions([$option]), implode('", "', array_keys($this->defined))));
        }
        if (!isset($this->allowedTypes[$option])) {
            $this->allowedTypes[$option] = (array) $allowedTypes;
        } else {
            $this->allowedTypes[$option] = array_merge($this->allowedTypes[$option], (array) $allowedTypes);
        }
        
        unset($this->resolved[$option]);
        return $this;
    }
    /**
     * @param string|string[] $optionNames
     * @return $this
     * @throws AccessException
     */
    public function remove($optionNames)
    {
        if ($this->locked) {
            throw new AccessException('Options cannot be removed from a lazy option or normalizer.');
        }
        foreach ((array) $optionNames as $option) {
            unset($this->defined[$option], $this->defaults[$option], $this->required[$option], $this->resolved[$option]);
            unset($this->lazy[$option], $this->normalizers[$option], $this->allowedTypes[$option], $this->allowedValues[$option]);
        }
        return $this;
    }
    /**
     * @return $this
     * @throws AccessException
     */
    public function clear()
    {
        if ($this->locked) {
            throw new AccessException('Options cannot be cleared from a lazy option or normalizer.');
        }
        $this->defined = [];
        $this->defaults = [];
        $this->nested = [];
        $this->required = [];
        $this->resolved = [];
        $this->lazy = [];
        $this->normalizers = [];
        $this->allowedTypes = [];
        $this->allowedValues = [];
        $this->deprecated = [];
        return $this;
    }
    /**
     * @return array
     * @throws UndefinedOptionsException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     * @throws OptionDefinitionException
     * @throws NoSuchOptionException
     * @throws AccessException
     */
    public function resolve(array $options = [])
    {
        if ($this->locked) {
            throw new AccessException('Options cannot be resolved from a lazy option or normalizer.');
        }
        
        $clone = clone $this;
        
        $diff = array_diff_key($options, $clone->defined);
        if (\count($diff) > 0) {
            ksort($clone->defined);
            ksort($diff);
            throw new UndefinedOptionsException(sprintf((\count($diff) > 1 ? 'The options "%s" do not exist.' : 'The option "%s" does not exist.') . ' Defined options are: "%s".', $this->formatOptions(array_keys($diff)), implode('", "', array_keys($clone->defined))));
        }
        
        foreach ($options as $option => $value) {
            $clone->given[$option] = \true;
            $clone->defaults[$option] = $value;
            unset($clone->resolved[$option], $clone->lazy[$option]);
        }
        
        $diff = array_diff_key($clone->required, $clone->defaults);
        if (\count($diff) > 0) {
            ksort($diff);
            throw new MissingOptionsException(sprintf(\count($diff) > 1 ? 'The required options "%s" are missing.' : 'The required option "%s" is missing.', $this->formatOptions(array_keys($diff))));
        }
        
        $clone->locked = \true;

        foreach ($clone->defaults as $option => $_) {
            $clone->offsetGet($option);
        }
        return $clone->resolved;
    }
    /**
     * @param string $option
     * @param bool $triggerDeprecation
     * @return mixed
     * @throws AccessException
     * @throws NoSuchOptionException
     * @throws InvalidOptionsException
     * @throws OptionDefinitionException
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($option)
    {
        if (!$this->locked) {
            throw new AccessException('Array access is only supported within closures of lazy options and normalizers.');
        }
        $triggerDeprecation = 1 === \func_num_args() || func_get_arg(1);
        
        if (isset($this->resolved[$option]) || \array_key_exists($option, $this->resolved)) {
            if ($triggerDeprecation && isset($this->deprecated[$option]) && (isset($this->given[$option]) || $this->calling) && \is_string($this->deprecated[$option])) {
                @trigger_error(strtr($this->deprecated[$option], ['%name%' => $option]), \E_USER_DEPRECATED);
            }
            return $this->resolved[$option];
        }
        
        if (!isset($this->defaults[$option]) && !\array_key_exists($option, $this->defaults)) {
            if (!isset($this->defined[$option])) {
                throw new NoSuchOptionException(sprintf('The option "%s" does not exist. Defined options are: "%s".', $this->formatOptions([$option]), implode('", "', array_keys($this->defined))));
            }
            throw new NoSuchOptionException(sprintf('The optional option "%s" has no value set. You should make sure it is set with "isset" before reading it.', $this->formatOptions([$option])));
        }
        $value = $this->defaults[$option];
        
        if (isset($this->nested[$option])) {
            if (isset($this->calling[$option])) {
                throw new OptionDefinitionException(sprintf('The options "%s" have a cyclic dependency.', $this->formatOptions(array_keys($this->calling))));
            }
            if (!\is_array($value)) {
                throw new InvalidOptionsException(sprintf('The nested option "%s" with value %s is expected to be of type array, but is of type "%s".', $this->formatOptions([$option]), $this->formatValue($value), $this->formatTypeOf($value)));
            }
            
            $this->calling[$option] = \true;
            try {
                $resolver = new self();
                $resolver->parentsOptions = $this->parentsOptions;
                $resolver->parentsOptions[] = $option;
                foreach ($this->nested[$option] as $closure) {
                    $closure($resolver, $this);
                }
                $value = $resolver->resolve($value);
            } finally {
                unset($this->calling[$option]);
            }
        }
        
        if (isset($this->lazy[$option])) {
            if (isset($this->calling[$option])) {
                throw new OptionDefinitionException(sprintf('The options "%s" have a cyclic dependency.', $this->formatOptions(array_keys($this->calling))));
            }

            $this->calling[$option] = \true;
            try {
                foreach ($this->lazy[$option] as $closure) {
                    $value = $closure($this, $value);
                }
            } finally {
                unset($this->calling[$option]);
            }
            
        }
        
        if (isset($this->allowedTypes[$option])) {
            $valid = \true;
            $invalidTypes = [];
            foreach ($this->allowedTypes[$option] as $type) {
                $type = self::TYPE_ALIASES[$type] ?? $type;
                if ($valid = $this->verifyTypes($type, $value, $invalidTypes)) {
                    break;
                }
            }
            if (!$valid) {
                $fmtActualValue = $this->formatValue($value);
                $fmtAllowedTypes = implode('" or "', $this->allowedTypes[$option]);
                $fmtProvidedTypes = implode('|', array_keys($invalidTypes));
                $allowedContainsArrayType = \count(array_filter($this->allowedTypes[$option], static function ($item) {
                    return str_ends_with(self::TYPE_ALIASES[$item] ?? $item, '[]');
                })) > 0;
                if (\is_array($value) && $allowedContainsArrayType) {
                    throw new InvalidOptionsException(sprintf('The option "%s" with value %s is expected to be of type "%s", but one of the elements is of type "%s".', $this->formatOptions([$option]), $fmtActualValue, $fmtAllowedTypes, $fmtProvidedTypes));
                }
                throw new InvalidOptionsException(sprintf('The option "%s" with value %s is expected to be of type "%s", but is of type "%s".', $this->formatOptions([$option]), $fmtActualValue, $fmtAllowedTypes, $fmtProvidedTypes));
            }
        }
        
        if (isset($this->allowedValues[$option])) {
            $success = \false;
            $printableAllowedValues = [];
            foreach ($this->allowedValues[$option] as $allowedValue) {
                if ($allowedValue instanceof \Closure) {
                    if ($allowedValue($value)) {
                        $success = \true;
                        break;
                    }
                    
                    continue;
                }
                if ($value === $allowedValue) {
                    $success = \true;
                    break;
                }
                $printableAllowedValues[] = $allowedValue;
            }
            if (!$success) {
                $message = sprintf('The option "%s" with value %s is invalid.', $option, $this->formatValue($value));
                if (\count($printableAllowedValues) > 0) {
                    $message .= sprintf(' Accepted values are: %s.', $this->formatValues($printableAllowedValues));
                }
                throw new InvalidOptionsException($message);
            }
        }

        if ($triggerDeprecation && isset($this->deprecated[$option]) && (isset($this->given[$option]) || $this->calling && \is_string($this->deprecated[$option]))) {
            $deprecationMessage = $this->deprecated[$option];
            if ($deprecationMessage instanceof \Closure) {
                if (isset($this->calling[$option])) {
                    throw new OptionDefinitionException(sprintf('The options "%s" have a cyclic dependency.', $this->formatOptions(array_keys($this->calling))));
                }
                $this->calling[$option] = \true;
                try {
                    if (!\is_string($deprecationMessage = $deprecationMessage($this, $value))) {
                        throw new InvalidOptionsException(sprintf('Invalid type for deprecation message, expected string but got "%s", return an empty string to ignore.', \gettype($deprecationMessage)));
                    }
                } finally {
                    unset($this->calling[$option]);
                }
            }
            if ('' !== $deprecationMessage) {
                @trigger_error(strtr($deprecationMessage, ['%name%' => $option]), \E_USER_DEPRECATED);
            }
        }
        
        if (isset($this->normalizers[$option])) {
            if (isset($this->calling[$option])) {
                throw new OptionDefinitionException(sprintf('The options "%s" have a cyclic dependency.', $this->formatOptions(array_keys($this->calling))));
            }

            $this->calling[$option] = \true;
            try {
                foreach ($this->normalizers[$option] as $normalizer) {
                    $value = $normalizer($this, $value);
                }
            } finally {
                unset($this->calling[$option]);
            }
            
        }
        
        $this->resolved[$option] = $value;
        return $value;
    }
    private function verifyTypes(string $type, $value, array &$invalidTypes, int $level = 0): bool
    {
        if (\is_array($value) && '[]' === substr($type, -2)) {
            $type = substr($type, 0, -2);
            $valid = \true;
            foreach ($value as $val) {
                if (!$this->verifyTypes($type, $val, $invalidTypes, $level + 1)) {
                    $valid = \false;
                }
            }
            return $valid;
        }
        if ('null' === $type && null === $value || \function_exists($func = 'is_' . $type) && $func($value) || $value instanceof $type) {
            return \true;
        }
        if (!$invalidTypes || $level > 0) {
            $invalidTypes[$this->formatTypeOf($value)] = \true;
        }
        return \false;
    }
    /**
     * @param string $option
     * @return bool
     * @throws AccessException
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($option)
    {
        if (!$this->locked) {
            throw new AccessException('Array access is only supported within closures of lazy options and normalizers.');
        }
        return \array_key_exists($option, $this->defaults);
    }
    /**
     * @return void
     * @throws AccessException
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($option, $value)
    {
        throw new AccessException('Setting options via array access is not supported. Use setDefault() instead.');
    }
    /**
     * @return void
     * @throws AccessException
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($option)
    {
        throw new AccessException('Removing options via array access is not supported. Use remove() instead.');
    }
    /**
     * @return int
     * @throws AccessException
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        if (!$this->locked) {
            throw new AccessException('Counting is only supported within closures of lazy options and normalizers.');
        }
        return \count($this->defaults);
    }
    /**
     * @param mixed $value
     * @return string
     */
    private function formatTypeOf($value): string
    {
        return \is_object($value) ? \get_class($value) : \gettype($value);
    }
    /**
     * @param mixed $value
     */
    private function formatValue($value): string
    {
        if (\is_object($value)) {
            return \get_class($value);
        }
        if (\is_array($value)) {
            return 'array';
        }
        if (\is_string($value)) {
            return '"' . $value . '"';
        }
        if (\is_resource($value)) {
            return 'resource';
        }
        if (null === $value) {
            return 'null';
        }
        if (\false === $value) {
            return 'false';
        }
        if (\true === $value) {
            return 'true';
        }
        return (string) $value;
    }
    
    private function formatValues(array $values): string
    {
        foreach ($values as $key => $value) {
            $values[$key] = $this->formatValue($value);
        }
        return implode(', ', $values);
    }
    private function formatOptions(array $options): string
    {
        if ($this->parentsOptions) {
            $prefix = array_shift($this->parentsOptions);
            if ($this->parentsOptions) {
                $prefix .= sprintf('[%s]', implode('][', $this->parentsOptions));
            }
            $options = array_map(static function (string $option) use ($prefix): string {
                return sprintf('%s[%s]', $prefix, $option);
            }, $options);
        }
        return implode('", "', $options);
    }
    private function getParameterClassName(\ReflectionParameter $parameter): ?string
    {
        if (!($type = $parameter->getType()) instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }
        return $type->getName();
    }
}
