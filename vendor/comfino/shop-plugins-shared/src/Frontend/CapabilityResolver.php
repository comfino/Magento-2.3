<?php

/**
 * Extended Comfino API client
 *
 * Ported from comfino/php-sdk (PHP 8.1+) to the PHP 7.1-compatible plugin distribution.
 *
 * @package Comfino\Frontend
 * @author Artur Kozubski <akozubski@comperia.pl>
 * @copyright Copyright (c) 2026 Comfino by Comperia.pl S.A.
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/comfino/extended-api-client
 */

declare(strict_types=1);

namespace Comfino\Frontend;

final class CapabilityResolver
{
    /**
     * @param string $family
     * @return array<string,
     */
    public static function fromThemeFamily(string $family): array
    {
        switch ($family) {
            case 'hyva':
                return [
                    'knockout' => false,
                    'alpine'   => true,
                    'tailwind' => true,
                    'requirejs' => false,
                    'jquery'   => false,
                ];
            case 'luma':
            case 'blank':
                return [
                    'knockout' => true,
                    'alpine'   => false,
                    'tailwind' => false,
                    'requirejs' => true,
                    'jquery'   => true,
                ];
            case 'classic':
            case 'storefront':
            case 'blocks':
                return [
                    'knockout' => false,
                    'alpine'   => false,
                    'tailwind' => false,
                    'requirejs' => false,
                    'jquery'   => true,
                ];
            default:
                return [
                    'knockout' => false,
                    'alpine'   => false,
                    'tailwind' => false,
                    'requirejs' => false,
                    'jquery'   => false,
                ];
        }
    }
}
