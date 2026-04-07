<?php

namespace ComfinoExternal;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
if (!\function_exists('ComfinoExternal\trigger_deprecation')) {
    /**
     * @param string $package
     * @param string $version
     * @param string $message
     */
    function trigger_deprecation(string $package, string $version, string $message, ...$args): void
    {
        @\trigger_error(($package || $version ? "Since {$package} {$version}: " : '') . ($args ? \vsprintf($message, $args) : $message), \E_USER_DEPRECATED);
    }
}
