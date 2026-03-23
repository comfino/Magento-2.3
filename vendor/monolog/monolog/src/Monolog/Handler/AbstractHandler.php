<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Monolog\Handler;

use ComfinoExternal\Monolog\Formatter\FormatterInterface;
use ComfinoExternal\Monolog\Formatter\LineFormatter;
use ComfinoExternal\Monolog\Logger;
use ComfinoExternal\Monolog\ResettableInterface;

abstract class AbstractHandler implements HandlerInterface, ResettableInterface
{
    protected $level = Logger::DEBUG;
    protected $bubble = \true;
    /**
     * @var FormatterInterface
     */
    protected $formatter;
    protected $processors = array();
    /**
     * @param int|string $level
     * @param bool $bubble
     */
    public function __construct($level = Logger::DEBUG, $bubble = \true)
    {
        $this->setLevel($level);
        $this->bubble = $bubble;
    }
    
    public function isHandling(array $record)
    {
        return $record['level'] >= $this->level;
    }
    
    public function handleBatch(array $records)
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }
    
    public function close()
    {
    }
    
    public function pushProcessor($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Processors must be valid callables (callback or object with an __invoke method), ' . var_export($callback, \true) . ' given');
        }
        array_unshift($this->processors, $callback);
        return $this;
    }
    
    public function popProcessor()
    {
        if (!$this->processors) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }
        return array_shift($this->processors);
    }
    
    public function setFormatter(FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
        return $this;
    }
    
    public function getFormatter()
    {
        if (!$this->formatter) {
            $this->formatter = $this->getDefaultFormatter();
        }
        return $this->formatter;
    }
    /**
     * @param int|string $level
     * @return self
     */
    public function setLevel($level)
    {
        $this->level = Logger::toMonologLevel($level);
        return $this;
    }
    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }
    /**
     * @param bool $bubble
     * @return self
     */
    public function setBubble($bubble)
    {
        $this->bubble = $bubble;
        return $this;
    }
    /**
     * @return bool
     */
    public function getBubble()
    {
        return $this->bubble;
    }
    public function __destruct()
    {
        try {
            $this->close();
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }
    }
    public function reset()
    {
        foreach ($this->processors as $processor) {
            if ($processor instanceof ResettableInterface) {
                $processor->reset();
            }
        }
    }
    /**
     * @return FormatterInterface
     */
    protected function getDefaultFormatter()
    {
        return new LineFormatter();
    }
}
