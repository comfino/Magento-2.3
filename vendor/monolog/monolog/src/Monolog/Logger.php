<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Monolog;

use ComfinoExternal\Monolog\Handler\HandlerInterface;
use ComfinoExternal\Monolog\Handler\StreamHandler;
use ComfinoExternal\Psr\Log\LoggerInterface;
use ComfinoExternal\Psr\Log\InvalidArgumentException;
use Exception;

class Logger implements LoggerInterface, ResettableInterface
{
    const DEBUG = 100;
    
    const INFO = 200;
    
    const NOTICE = 250;
    
    const WARNING = 300;
    
    const ERROR = 400;
    
    const CRITICAL = 500;
    
    const ALERT = 550;
    
    const EMERGENCY = 600;
    /**
     * @var int
     */
    const API = 1;
    /**
     * @var array $levels
     */
    protected static $levels = array(self::DEBUG => 'DEBUG', self::INFO => 'INFO', self::NOTICE => 'NOTICE', self::WARNING => 'WARNING', self::ERROR => 'ERROR', self::CRITICAL => 'CRITICAL', self::ALERT => 'ALERT', self::EMERGENCY => 'EMERGENCY');
    /**
     * @var \DateTimeZone
     */
    protected static $timezone;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var HandlerInterface[]
     */
    protected $handlers;
    /**
     * @var callable[]
     */
    protected $processors;
    /**
     * @var bool
     */
    protected $microsecondTimestamps = \true;
    /**
     * @var callable
     */
    protected $exceptionHandler;
    /**
     * @param string $name
     * @param HandlerInterface[] $handlers
     * @param callable[] $processors
     */
    public function __construct($name, array $handlers = array(), array $processors = array())
    {
        $this->name = $name;
        $this->setHandlers($handlers);
        $this->processors = $processors;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @return static
     */
    public function withName($name)
    {
        $new = clone $this;
        $new->name = $name;
        return $new;
    }
    /**
     * @param HandlerInterface $handler
     * @return $this
     */
    public function pushHandler(HandlerInterface $handler)
    {
        array_unshift($this->handlers, $handler);
        return $this;
    }
    /**
     * @return HandlerInterface
     */
    public function popHandler()
    {
        if (!$this->handlers) {
            throw new \LogicException('You tried to pop from an empty handler stack.');
        }
        return array_shift($this->handlers);
    }
    /**
     * @param HandlerInterface[] $handlers
     * @return $this
     */
    public function setHandlers(array $handlers)
    {
        $this->handlers = array();
        foreach (array_reverse($handlers) as $handler) {
            $this->pushHandler($handler);
        }
        return $this;
    }
    /**
     * @return HandlerInterface[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }
    /**
     * @param callable $callback
     * @return $this
     */
    public function pushProcessor($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Processors must be valid callables (callback or object with an __invoke method), ' . var_export($callback, \true) . ' given');
        }
        array_unshift($this->processors, $callback);
        return $this;
    }
    /**
     * @return callable
     */
    public function popProcessor()
    {
        if (!$this->processors) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }
        return array_shift($this->processors);
    }
    /**
     * @return callable[]
     */
    public function getProcessors()
    {
        return $this->processors;
    }
    /**
     * @param bool $micro
     */
    public function useMicrosecondTimestamps($micro)
    {
        $this->microsecondTimestamps = (bool) $micro;
    }
    /**
     * @param int $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addRecord($level, $message, array $context = array())
    {
        if (!$this->handlers) {
            $this->pushHandler(new StreamHandler('php://stderr', static::DEBUG));
        }
        $levelName = static::getLevelName($level);
        
        $handlerKey = null;
        reset($this->handlers);
        while ($handler = current($this->handlers)) {
            if ($handler->isHandling(array('level' => $level))) {
                $handlerKey = key($this->handlers);
                break;
            }
            next($this->handlers);
        }
        if (null === $handlerKey) {
            return \false;
        }
        if (!static::$timezone) {
            static::$timezone = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }
        
        if ($this->microsecondTimestamps && \PHP_VERSION_ID < 70100) {
            $ts = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(\true)), static::$timezone);
        } else {
            $ts = new \DateTime('now', static::$timezone);
        }
        $ts->setTimezone(static::$timezone);
        $record = array('message' => (string) $message, 'context' => $context, 'level' => $level, 'level_name' => $levelName, 'channel' => $this->name, 'datetime' => $ts, 'extra' => array());
        try {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
            while ($handler = current($this->handlers)) {
                if (\true === $handler->handle($record)) {
                    break;
                }
                next($this->handlers);
            }
        } catch (Exception $e) {
            $this->handleException($e, $record);
        }
        return \true;
    }
    
    public function close()
    {
        foreach ($this->handlers as $handler) {
            if (method_exists($handler, 'close')) {
                $handler->close();
            }
        }
    }
    
    public function reset()
    {
        foreach ($this->handlers as $handler) {
            if ($handler instanceof ResettableInterface) {
                $handler->reset();
            }
        }
        foreach ($this->processors as $processor) {
            if ($processor instanceof ResettableInterface) {
                $processor->reset();
            }
        }
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addDebug($message, array $context = array())
    {
        return $this->addRecord(static::DEBUG, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addInfo($message, array $context = array())
    {
        return $this->addRecord(static::INFO, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addNotice($message, array $context = array())
    {
        return $this->addRecord(static::NOTICE, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addWarning($message, array $context = array())
    {
        return $this->addRecord(static::WARNING, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addError($message, array $context = array())
    {
        return $this->addRecord(static::ERROR, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addCritical($message, array $context = array())
    {
        return $this->addRecord(static::CRITICAL, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addAlert($message, array $context = array())
    {
        return $this->addRecord(static::ALERT, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function addEmergency($message, array $context = array())
    {
        return $this->addRecord(static::EMERGENCY, $message, $context);
    }
    /**
     * @return array
     */
    public static function getLevels()
    {
        return array_flip(static::$levels);
    }
    /**
     * @param int $level
     * @return string
     */
    public static function getLevelName($level)
    {
        if (!isset(static::$levels[$level])) {
            throw new InvalidArgumentException('Level "' . $level . '" is not defined, use one of: ' . implode(', ', array_keys(static::$levels)));
        }
        return static::$levels[$level];
    }
    /**
     * @param string|int $level
     * @return int
     */
    public static function toMonologLevel($level)
    {
        if (is_string($level)) {
            $upper = strtr($level, 'abcdefgilmnortuwy', 'ABCDEFGILMNORTUWY');
            if (defined(__CLASS__ . '::' . $upper)) {
                return constant(__CLASS__ . '::' . $upper);
            }
        }
        return $level;
    }
    /**
     * @param int $level
     * @return bool
     */
    public function isHandling($level)
    {
        $record = array('level' => $level);
        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($record)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param callable $callback
     * @return $this
     */
    public function setExceptionHandler($callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Exception handler must be valid callable (callback or object with an __invoke method), ' . var_export($callback, \true) . ' given');
        }
        $this->exceptionHandler = $callback;
        return $this;
    }
    /**
     * @return callable
     */
    public function getExceptionHandler()
    {
        return $this->exceptionHandler;
    }
    
    protected function handleException(Exception $e, array $record)
    {
        if (!$this->exceptionHandler) {
            throw $e;
        }
        call_user_func($this->exceptionHandler, $e, $record);
    }
    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function log($level, $message, array $context = array())
    {
        $level = static::toMonologLevel($level);
        return $this->addRecord($level, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function debug($message, array $context = array())
    {
        return $this->addRecord(static::DEBUG, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = array())
    {
        return $this->addRecord(static::INFO, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function notice($message, array $context = array())
    {
        return $this->addRecord(static::NOTICE, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function warn($message, array $context = array())
    {
        return $this->addRecord(static::WARNING, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function warning($message, array $context = array())
    {
        return $this->addRecord(static::WARNING, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function err($message, array $context = array())
    {
        return $this->addRecord(static::ERROR, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function error($message, array $context = array())
    {
        return $this->addRecord(static::ERROR, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function crit($message, array $context = array())
    {
        return $this->addRecord(static::CRITICAL, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function critical($message, array $context = array())
    {
        return $this->addRecord(static::CRITICAL, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function alert($message, array $context = array())
    {
        return $this->addRecord(static::ALERT, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function emerg($message, array $context = array())
    {
        return $this->addRecord(static::EMERGENCY, $message, $context);
    }
    /**
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function emergency($message, array $context = array())
    {
        return $this->addRecord(static::EMERGENCY, $message, $context);
    }
    /**
     * @param \DateTimeZone $tz
     */
    public static function setTimezone(\DateTimeZone $tz)
    {
        self::$timezone = $tz;
    }
}
