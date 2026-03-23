<?php

namespace ComfinoExternal\League\Flysystem;

trait ConfigAwareTrait
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @param Config|array|null $config
     */
    protected function setConfig($config)
    {
        $this->config = $config ? Util::ensureConfig($config) : new Config();
    }
    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
    /**
     * @param array $config
     * @return Config
     */
    protected function prepareConfig(array $config)
    {
        $config = new Config($config);
        $config->setFallback($this->getConfig());
        return $config;
    }
}
