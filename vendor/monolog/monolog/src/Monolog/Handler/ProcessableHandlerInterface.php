<?php

declare (strict_types=1);
/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Monolog\Handler;

use ComfinoExternal\Monolog\Processor\ProcessorInterface;

interface ProcessableHandlerInterface
{
    /**
     * @param ProcessorInterface|callable $callback
     * @return HandlerInterface
     */
    public function pushProcessor($callback): HandlerInterface;
    /**
     * @throws \LogicException
     * @return callable
     */
    public function popProcessor(): callable;
}
