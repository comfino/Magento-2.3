<?php

namespace ComfinoExternal\League\Flysystem\Adapter;

use DateTime;
use ComfinoExternal\League\Flysystem\AdapterInterface;
use ComfinoExternal\League\Flysystem\Config;
use ComfinoExternal\League\Flysystem\NotSupportedException;
use ComfinoExternal\League\Flysystem\SafeStorage;
use RuntimeException;
abstract class AbstractFtpAdapter extends AbstractAdapter
{
    /**
     * @var mixed
     */
    protected $connection;
    /**
     * @var string
     */
    protected $host;
    /**
     * @var int
     */
    protected $port = 21;
    /**
     * @var bool
     */
    protected $ssl = \false;
    /**
     * @var int
     */
    protected $timeout = 90;
    /**
     * @var bool
     */
    protected $passive = \true;
    /**
     * @var string
     */
    protected $separator = '/';
    /**
     * @var string|null
     */
    protected $root;
    /**
     * @var int
     */
    protected $permPublic = 0744;
    /**
     * @var int
     */
    protected $permPrivate = 0700;
    /**
     * @var array
     */
    protected $configurable = [];
    /**
     * @var string
     */
    protected $systemType;
    /**
     * @var SafeStorage
     */
    protected $safeStorage;
    /**
     * @var bool
     */
    protected $enableTimestampsOnUnixListings = \false;
    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->safeStorage = new SafeStorage();
        $this->setConfig($config);
    }
    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config)
    {
        foreach ($this->configurable as $setting) {
            if (!isset($config[$setting])) {
                continue;
            }
            $method = 'set' . ucfirst($setting);
            if (method_exists($this, $method)) {
                $this->{$method}($config[$setting]);
            }
        }
        return $this;
    }
    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }
    /**
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }
    /**
     * @param int $permPublic
     * @return $this
     */
    public function setPermPublic($permPublic)
    {
        $this->permPublic = $permPublic;
        return $this;
    }
    /**
     * @param int $permPrivate
     * @return $this
     */
    public function setPermPrivate($permPrivate)
    {
        $this->permPrivate = $permPrivate;
        return $this;
    }
    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }
    /**
     * @return string
     */
    public function getRoot()
    {
        return $this->root;
    }
    /**
     * @param int|string $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = (int) $port;
        return $this;
    }
    /**
     * @param string $root
     * @return $this
     */
    public function setRoot($root)
    {
        $this->root = rtrim($root, '\/') . $this->separator;
        return $this;
    }
    /**
     * @return string
     */
    public function getUsername()
    {
        $username = $this->safeStorage->retrieveSafely('username');
        return $username !== null ? $username : 'anonymous';
    }
    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->safeStorage->storeSafely('username', $username);
        return $this;
    }
    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->safeStorage->retrieveSafely('password');
    }
    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->safeStorage->storeSafely('password', $password);
        return $this;
    }
    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
    /**
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;
        return $this;
    }
    /**
     * @return string
     */
    public function getSystemType()
    {
        return $this->systemType;
    }
    /**
     * @param string $systemType
     * @return $this
     */
    public function setSystemType($systemType)
    {
        $this->systemType = strtolower($systemType);
        return $this;
    }
    /**
     * @param bool $bool
     * @return $this
     */
    public function setEnableTimestampsOnUnixListings($bool = \false)
    {
        $this->enableTimestampsOnUnixListings = $bool;
        return $this;
    }
    
    public function listContents($directory = '', $recursive = \false)
    {
        return $this->listDirectoryContents($directory, $recursive);
    }
    abstract protected function listDirectoryContents($directory, $recursive = \false);
    /**
     * @param array $listing
     * @param string $prefix
     * @return array
     */
    protected function normalizeListing(array $listing, $prefix = '')
    {
        $base = $prefix;
        $result = [];
        $listing = $this->removeDotDirectories($listing);
        while ($item = array_shift($listing)) {
            if (preg_match('#^.*:$#', $item)) {
                $base = preg_replace('~^\./*|:$~', '', $item);
                continue;
            }
            $result[] = $this->normalizeObject($item, $base);
        }
        return $this->sortListing($result);
    }
    /**
     * @param array $result
     * @return array
     */
    protected function sortListing(array $result)
    {
        $compare = function ($one, $two) {
            return strnatcmp($one['path'], $two['path']);
        };
        usort($result, $compare);
        return $result;
    }
    /**
     * @param string $item
     * @param string $base
     * @return array
     * @throws NotSupportedException
     */
    protected function normalizeObject($item, $base)
    {
        $systemType = $this->systemType ?: $this->detectSystemType($item);
        if ($systemType === 'unix') {
            return $this->normalizeUnixObject($item, $base);
        } elseif ($systemType === 'windows') {
            return $this->normalizeWindowsObject($item, $base);
        }
        throw NotSupportedException::forFtpSystemType($systemType);
    }
    /**
     * @param string $item
     * @param string $base
     * @return array
     */
    protected function normalizeUnixObject($item, $base)
    {
        $item = preg_replace('#\s+#', ' ', trim($item), 7);
        if (count(explode(' ', $item, 9)) !== 9) {
            throw new RuntimeException("Metadata can't be parsed from item '{$item}' , not enough parts.");
        }
        list($permissions, , , , $size, $month, $day, $timeOrYear, $name) = explode(' ', $item, 9);
        $type = $this->detectType($permissions);
        $path = $base === '' ? $name : $base . $this->separator . $name;
        if ($type === 'dir') {
            return compact('type', 'path');
        }
        $permissions = $this->normalizePermissions($permissions);
        $visibility = $permissions & 044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;
        $size = (int) $size;
        $result = compact('type', 'path', 'visibility', 'size');
        if ($this->enableTimestampsOnUnixListings) {
            $timestamp = $this->normalizeUnixTimestamp($month, $day, $timeOrYear);
            $result += compact('timestamp');
        }
        return $result;
    }
    /**
     * @param string $month
     * @param string $day
     * @param string $timeOrYear
     * @return int
     */
    protected function normalizeUnixTimestamp($month, $day, $timeOrYear)
    {
        if (is_numeric($timeOrYear)) {
            $year = $timeOrYear;
            $hour = '00';
            $minute = '00';
            $seconds = '00';
        } else {
            $year = date('Y');
            list($hour, $minute) = explode(':', $timeOrYear);
            $seconds = '00';
        }
        $dateTime = DateTime::createFromFormat('Y-M-j-G:i:s', "{$year}-{$month}-{$day}-{$hour}:{$minute}:{$seconds}");
        return $dateTime->getTimestamp();
    }
    /**
     * @param string $item
     * @param string $base
     * @return array
     */
    protected function normalizeWindowsObject($item, $base)
    {
        $item = preg_replace('#\s+#', ' ', trim($item), 3);
        if (count(explode(' ', $item, 4)) !== 4) {
            throw new RuntimeException("Metadata can't be parsed from item '{$item}' , not enough parts.");
        }
        list($date, $time, $size, $name) = explode(' ', $item, 4);
        $path = $base === '' ? $name : $base . $this->separator . $name;
        
        $format = strlen($date) === 8 ? 'm-d-yH:iA' : 'Y-m-dH:i';
        $dt = DateTime::createFromFormat($format, $date . $time);
        $timestamp = $dt ? $dt->getTimestamp() : (int) strtotime("{$date} {$time}");
        if ($size === '<DIR>') {
            $type = 'dir';
            return compact('type', 'path', 'timestamp');
        }
        $type = 'file';
        $visibility = AdapterInterface::VISIBILITY_PUBLIC;
        $size = (int) $size;
        return compact('type', 'path', 'visibility', 'size', 'timestamp');
    }
    /**
     * @param string $item
     * @return string
     */
    protected function detectSystemType($item)
    {
        return preg_match('/^[0-9]{2,4}-[0-9]{2}-[0-9]{2}/', $item) ? 'windows' : 'unix';
    }
    /**
     * @param string $permissions
     * @return string
     */
    protected function detectType($permissions)
    {
        return substr($permissions, 0, 1) === 'd' ? 'dir' : 'file';
    }
    /**
     * @param string $permissions
     * @return int
     */
    protected function normalizePermissions($permissions)
    {
        if (is_numeric($permissions)) {
            return (int) $permissions & 0777;
        }
        
        $permissions = substr($permissions, 1);
        
        $map = ['-' => '0', 'r' => '4', 'w' => '2', 'x' => '1'];
        $permissions = strtr($permissions, $map);
        
        $parts = str_split($permissions, 3);
        
        $mapper = function ($part) {
            return array_sum(str_split($part));
        };
        
        return octdec(implode('', array_map($mapper, $parts)));
    }
    /**
     * @param array $list
     * @return array
     */
    public function removeDotDirectories(array $list)
    {
        $filter = function ($line) {
            return $line !== '' && !preg_match('#.* \.(\.)?$|^total#', $line);
        };
        return array_filter($list, $filter);
    }
    
    public function has($path)
    {
        return $this->getMetadata($path);
    }
    
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }
    
    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }
    /**
     * @param string $dirname
     */
    public function ensureDirectory($dirname)
    {
        $dirname = (string) $dirname;
        if ($dirname !== '' && !$this->has($dirname)) {
            $this->createDir($dirname, new Config());
        }
    }
    /**
     * @return mixed
     */
    public function getConnection()
    {
        $tries = 0;
        while (!$this->isConnected() && $tries < 3) {
            $tries++;
            $this->disconnect();
            $this->connect();
        }
        return $this->connection;
    }
    /**
     * @return int
     */
    public function getPermPublic()
    {
        return $this->permPublic;
    }
    /**
     * @return int
     */
    public function getPermPrivate()
    {
        return $this->permPrivate;
    }
    
    public function __destruct()
    {
        $this->disconnect();
    }
    
    abstract public function connect();
    
    abstract public function disconnect();
    /**
     * @return bool
     */
    abstract public function isConnected();
}
