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

use ComfinoExternal\Psr\Http\Message\UriInterface;
use ComfinoExternal\Sunrise\Uri\Component\Scheme;
use ComfinoExternal\Sunrise\Uri\Component\UserInfo;
use ComfinoExternal\Sunrise\Uri\Component\Host;
use ComfinoExternal\Sunrise\Uri\Component\Port;
use ComfinoExternal\Sunrise\Uri\Component\Path;
use ComfinoExternal\Sunrise\Uri\Component\Query;
use ComfinoExternal\Sunrise\Uri\Component\Fragment;

use function getservbyname;
use function ltrim;
use function strncmp;

class Uri implements UriInterface
{
    /**
     * @var string
     */
    protected $scheme = '';
    /**
     * @var string
     */
    protected $userinfo = '';
    /**
     * @var string
     */
    protected $host = '';
    /**
     * @var int|null
     */
    protected $port;
    /**
     * @var string
     */
    protected $path = '';
    /**
     * @var string
     */
    protected $query = '';
    /**
     * @var string
     */
    protected $fragment = '';
    /**
     * @param mixed $uri
     */
    public function __construct($uri = '')
    {
        if ($uri === '') {
            return;
        }
        $parsedUri = new UriParser($uri);
        $scheme = $parsedUri->getScheme();
        if (isset($scheme)) {
            $this->scheme = $scheme->present();
        }
        $userinfo = $parsedUri->getUserInfo();
        if (isset($userinfo)) {
            $this->userinfo = $userinfo->present();
        }
        $host = $parsedUri->getHost();
        if (isset($host)) {
            $this->host = $host->present();
        }
        $port = $parsedUri->getPort();
        if (isset($port)) {
            $this->port = $port->present();
        }
        $path = $parsedUri->getPath();
        if (isset($path)) {
            $this->path = $path->present();
        }
        $query = $parsedUri->getQuery();
        if (isset($query)) {
            $this->query = $query->present();
        }
        $fragment = $parsedUri->getFragment();
        if (isset($fragment)) {
            $this->fragment = $fragment->present();
        }
    }
    
    public function withScheme($scheme): UriInterface
    {
        $clone = clone $this;
        $component = new Scheme($scheme);
        $clone->scheme = $component->present();
        return $clone;
    }
    /**
     * @psalm-suppress ParamNameMismatch
     */
    public function withUserInfo($user, $pass = null): UriInterface
    {
        $clone = clone $this;
        $component = new UserInfo($user, $pass);
        $clone->userinfo = $component->present();
        return $clone;
    }
    
    public function withHost($host): UriInterface
    {
        $clone = clone $this;
        $component = new Host($host);
        $clone->host = $component->present();
        return $clone;
    }
    
    public function withPort($port): UriInterface
    {
        $clone = clone $this;
        $component = new Port($port);
        $clone->port = $component->present();
        return $clone;
    }
    
    public function withPath($path): UriInterface
    {
        $clone = clone $this;
        $component = new Path($path);
        $clone->path = $component->present();
        return $clone;
    }
    
    public function withQuery($query): UriInterface
    {
        $clone = clone $this;
        $component = new Query($query);
        $clone->query = $component->present();
        return $clone;
    }
    
    public function withFragment($fragment): UriInterface
    {
        $clone = clone $this;
        $component = new Fragment($fragment);
        $clone->fragment = $component->present();
        return $clone;
    }
    
    public function getScheme(): string
    {
        return $this->scheme;
    }
    
    public function getUserInfo(): string
    {
        return $this->userinfo;
    }
    
    public function getHost(): string
    {
        return $this->host;
    }
    
    public function getPort(): ?int
    {
        if ($this->port === 80 && $this->scheme === 'http') {
            return null;
        }
        
        if ($this->port === 443 && $this->scheme === 'https') {
            return null;
        }
        return $this->port;
    }
    /**
     * @return int|null
     */
    public function getStandardPort(): ?int
    {
        $servicePort = getservbyname($this->scheme, 'tcp');
        if ($servicePort !== \false) {
            return $servicePort;
        }
        $servicePort = getservbyname($this->scheme, 'udp');
        if ($servicePort !== \false) {
            return $servicePort;
        }
        return null;
    }
    
    public function getPath(): string
    {
        return $this->path;
    }
    
    public function getQuery(): string
    {
        return $this->query;
    }
    
    public function getFragment(): string
    {
        return $this->fragment;
    }
    
    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }
        $authority = $this->host;
        if ($this->userinfo !== '') {
            $authority = $this->userinfo . '@' . $authority;
        }
        $port = $this->getPort();
        if ($port !== null) {
            $authority = $authority . ':' . $port;
        }
        return $authority;
    }
    
    public function __toString()
    {
        $uri = '';
        $scheme = $this->getScheme();
        if ($scheme !== '') {
            $uri .= $scheme . ':';
        }
        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }
        $path = $this->getPath();
        if ($path !== '') {
            if ($authority !== '' && strncmp($path, '/', 1) !== 0) {
                $path = '/' . $path;
            }

            if ($authority === '' && strncmp($path, '//', 2) === 0) {
                $path = '/' . ltrim($path, '/');
            }
            $uri .= $path;
        }
        $query = $this->getQuery();
        if ($query !== '') {
            $uri .= '?' . $query;
        }
        $fragment = $this->getFragment();
        if ($fragment !== '') {
            $uri .= '#' . $fragment;
        }
        return $uri;
    }
}
