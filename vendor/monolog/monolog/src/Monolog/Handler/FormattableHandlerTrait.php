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
use ComfinoExternal\Monolog\Formatter\LineFormatter;

trait FormattableHandlerTrait
{
    /**
     * @var FormatterInterface
     */
    protected $formatter;
    
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        $this->formatter = $formatter;
        return $this;
    }
    
    public function getFormatter(): FormatterInterface
    {
        if (!$this->formatter) {
            $this->formatter = $this->getDefaultFormatter();
        }
        return $this->formatter;
    }
    
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter();
    }
}
