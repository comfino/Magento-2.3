<?php

namespace ComfinoExternal\League\Flysystem;

class Config
{
    /**
     * @var array
     */
    protected $settings = [];
    /**
     * @var Config|null
     */
    protected $fallback;
    /**
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!array_key_exists($key, $this->settings)) {
            return $this->getDefault($key, $default);
        }
        return $this->settings[$key];
    }
    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        if (array_key_exists($key, $this->settings)) {
            return \true;
        }
        return $this->fallback instanceof Config ? $this->fallback->has($key) : \false;
    }
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getDefault($key, $default)
    {
        if (!$this->fallback) {
            return $default;
        }
        return $this->fallback->get($key, $default);
    }
    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->settings[$key] = $value;
        return $this;
    }
    /**
     * @param Config $fallback
     * @return $this
     */
    public function setFallback(Config $fallback)
    {
        $this->fallback = $fallback;
        return $this;
    }
}
