<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Monolog\Handler;

use ComfinoExternal\Monolog\ResettableInterface;

abstract class AbstractProcessingHandler extends AbstractHandler
{
    public function handle(array $record)
    {
        if (!$this->isHandling($record)) {
            return \false;
        }
        $record = $this->processRecord($record);
        $record['formatted'] = $this->getFormatter()->format($record);
        $this->write($record);
        return \false === $this->bubble;
    }
    /**
     * @param array $record
     * @return void
     */
    abstract protected function write(array $record);
    /**
     * @param array $record
     * @return array
     */
    protected function processRecord(array $record)
    {
        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }
        return $record;
    }
}
