<?php

declare (strict_types=1);
/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2018, Anatoly Fenric
 * @license https://github.com/sunrise-php/uri/blob/master/LICENSE
 * @link https://github.com/sunrise-php/uri
 */
namespace ComfinoExternal\Sunrise\Uri;

use ComfinoExternal\Sunrise\Uri\Component\ComponentInterface;
use ComfinoExternal\Sunrise\Uri\Component\Scheme;
use ComfinoExternal\Sunrise\Uri\Component\User;
use ComfinoExternal\Sunrise\Uri\Component\Pass;
use ComfinoExternal\Sunrise\Uri\Component\Host;
use ComfinoExternal\Sunrise\Uri\Component\Port;
use ComfinoExternal\Sunrise\Uri\Component\Path;
use ComfinoExternal\Sunrise\Uri\Component\Query;
use ComfinoExternal\Sunrise\Uri\Component\Fragment;
use ComfinoExternal\Sunrise\Uri\Component\UserInfo;
use ComfinoExternal\Sunrise\Uri\Exception\InvalidUriException;

use function is_string;
use function parse_url;

class UriParser
{
    /**
     * @var Scheme|null
     */
    protected $scheme;
    /**
     * @var User|null
     */
    protected $user;
    /**
     * @var Pass|null
     */
    protected $pass;
    /**
     * @var Host|null
     */
    protected $host;
    /**
     * @var Port|null
     */
    protected $port;
    /**
     * @var Path|null
     */
    protected $path;
    /**
     * @var Query|null
     */
    protected $query;
    /**
     * @var Fragment|null
     */
    protected $fragment;
    /**
     * @param mixed $uri
     * @throws InvalidUriException
     */
    public function __construct($uri)
    {
        if ($uri === '') {
            return;
        }
        if (!is_string($uri)) {
            throw new InvalidUriException('URI must be a string');
        }
        $components = parse_url($uri);
        if ($components === \false) {
            throw new InvalidUriException('Unable to parse URI');
        }
        if (isset($components['scheme'])) {
            $this->scheme = new Scheme($components['scheme']);
        }
        if (isset($components['user'])) {
            $this->user = new User($components['user']);
        }
        if (isset($components['pass'])) {
            $this->pass = new Pass($components['pass']);
        }
        if (isset($components['host'])) {
            $this->host = new Host($components['host']);
        }
        if (isset($components['port'])) {
            $this->port = new Port($components['port']);
        }
        if (isset($components['path'])) {
            $this->path = new Path($components['path']);
        }
        if (isset($components['query'])) {
            $this->query = new Query($components['query']);
        }
        if (isset($components['fragment'])) {
            $this->fragment = new Fragment($components['fragment']);
        }
    }
    /**
     * @return Scheme|null
     */
    public function getScheme(): ?Scheme
    {
        return $this->scheme;
    }
    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
    /**
     * @return Pass|null
     */
    public function getPass(): ?Pass
    {
        return $this->pass;
    }
    /**
     * @return Host|null
     */
    public function getHost(): ?Host
    {
        return $this->host;
    }
    /**
     * @return Port|null
     */
    public function getPort(): ?Port
    {
        return $this->port;
    }
    /**
     * @return Path|null
     */
    public function getPath(): ?Path
    {
        return $this->path;
    }
    /**
     * @return Query|null
     */
    public function getQuery(): ?Query
    {
        return $this->query;
    }
    /**
     * @return Fragment|null
     */
    public function getFragment(): ?Fragment
    {
        return $this->fragment;
    }
    /**
     * @return UserInfo|null
     */
    public function getUserInfo(): ?UserInfo
    {
        if (isset($this->user)) {
            return new UserInfo($this->user, $this->pass);
        }
        return null;
    }
}
