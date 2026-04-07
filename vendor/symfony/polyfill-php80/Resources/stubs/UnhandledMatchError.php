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

class UnhandledMatchError extends \Error
{
}
if (!class_exists('UnhandledMatchError', false) && !interface_exists('UnhandledMatchError', false)) { \class_alias('ComfinoExternal\UnhandledMatchError', 'UnhandledMatchError', \false); }

