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

class ValueError extends \Error
{
}
if (!class_exists('ValueError', false) && !interface_exists('ValueError', false)) { \class_alias('ComfinoExternal\ValueError', 'ValueError', \false); }

