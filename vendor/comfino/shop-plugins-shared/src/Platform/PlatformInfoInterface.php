<?php

/**
 * Extended Comfino API client
 *
 * Ported from comfino/php-sdk (PHP 8.1+) to the PHP 7.1-compatible plugin distribution.
 *
 * @package Comfino\Platform
 * @author Artur Kozubski <akozubski@comperia.pl>
 * @copyright Copyright (c) 2026 Comfino by Comperia.pl S.A.
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/comfino/extended-api-client
 */

declare(strict_types=1);

namespace Comfino\Platform;

interface PlatformInfoInterface
{
    public function getCode(): string;

    public function getName(): string;

    public function getVersion(): string;

    public function getLanguage(): string;

    public function getCurrency(): string;

    public function getDomain(): string;

    public function getDatabaseVersion(): string;

    public function getPhpVersion(): string;

    public function getPluginVersion(): string;

    /**
     * @return array<string,
     */
    public function toArray(): array;
}
