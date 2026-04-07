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

interface Stringable
{
    /**
     * @return string
     */
    public function __toString();
}
if (!class_exists('Stringable', false) && !interface_exists('Stringable', false)) { \class_alias('ComfinoExternal\Stringable', 'Stringable', \false); }

