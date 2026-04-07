<?php

namespace ComfinoExternal\Psr\Http\Message;

interface UriInterface
{
    /**
     * @return string
     */
    public function getScheme();
    /**
     * @return string
     */
    public function getAuthority();
    /**
     * @return string
     */
    public function getUserInfo();
    /**
     * @return string
     */
    public function getHost();
    /**
     * @return null|int
     */
    public function getPort();
    /**
     * @return string
     */
    public function getPath();
    /**
     * @return string
     */
    public function getQuery();
    /**
     * @return string
     */
    public function getFragment();
    /**
     * @param string $scheme
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withScheme($scheme);
    /**
     * @param string $user
     * @param null|string $password
     * @return static
     */
    public function withUserInfo($user, $password = null);
    /**
     * @param string $host
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withHost($host);
    /**
     * @param null|int $port
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withPort($port);
    /**
     * @param string $path
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withPath($path);
    /**
     * @param string $query
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withQuery($query);
    /**
     * @param string $fragment
     * @return static
     */
    public function withFragment($fragment);
    /**
     * @return string
     */
    public function __toString();
}
