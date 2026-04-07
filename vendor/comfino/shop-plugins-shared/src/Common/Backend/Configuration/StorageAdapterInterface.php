<?php

declare(strict_types=1);

namespace Comfino\Common\Backend\Configuration;

interface StorageAdapterInterface
{
    public function load(): array;
    /**
     * @param mixed[] $configurationOptions
     */
    public function save($configurationOptions): void;
}
