<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Monolog\Formatter;

interface FormatterInterface
{
    /**
     * @param array $record
     * @return mixed
     */
    public function format(array $record);
    /**
     * @param array $records
     * @return mixed
     */
    public function formatBatch(array $records);
}
