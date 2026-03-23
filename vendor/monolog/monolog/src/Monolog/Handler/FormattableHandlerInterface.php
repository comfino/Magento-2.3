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

use ComfinoExternal\Monolog\Formatter\FormatterInterface;

interface FormattableHandlerInterface
{
    /**
     * @param FormatterInterface $formatter
     * @return HandlerInterface
     */
    public function setFormatter(FormatterInterface $formatter): HandlerInterface;
    /**
     * @return FormatterInterface
     */
    public function getFormatter(): FormatterInterface;
}
