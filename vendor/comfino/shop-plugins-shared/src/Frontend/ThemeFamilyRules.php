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

final class ThemeFamilyRules
{
    private $rules = [];

    /**
     * @param string $family
     */
    public function register(string $family, callable $predicate): void
    {
        $this->rules[$family] = $predicate;
    }

    /**
     * @param string[] $themeChain
     * @return string
     */
    public function resolveFamily(array $themeChain): string
    {
        $lowercasedChain = array_map('strtolower', $themeChain);

        foreach ($this->rules as $family => $predicate) {
            if ($predicate($lowercasedChain)) {
                return $family;
            }
        }

        return 'custom';
    }
}
