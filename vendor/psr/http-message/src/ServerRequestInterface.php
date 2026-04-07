<?php

namespace ComfinoExternal\Psr\Http\Message;

interface ServerRequestInterface extends RequestInterface
{
    /**
     * @return array
     */
    public function getServerParams();
    /**
     * @return array
     */
    public function getCookieParams();
    /**
     * @param array $cookies
     * @return static
     */
    public function withCookieParams(array $cookies);
    /**
     * @return array
     */
    public function getQueryParams();
    /**
     * @param array $query
     * @return static
     */
    public function withQueryParams(array $query);
    /**
     * @return array
     */
    public function getUploadedFiles();
    /**
     * @param array $uploadedFiles
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withUploadedFiles(array $uploadedFiles);
    /**
     * @return null|array|object
     */
    public function getParsedBody();
    /**
     * @param null|array|object $data
     * @return static
     * @throws \InvalidArgumentException
     */
    public function withParsedBody($data);
    /**
     * @return array
     */
    public function getAttributes();
    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($name, $default = null);
    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function withAttribute($name, $value);
    /**
     * @param string $name
     * @return static
     */
    public function withoutAttribute($name);
}
