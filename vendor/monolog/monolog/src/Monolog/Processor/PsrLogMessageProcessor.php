<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Monolog\Processor;

use ComfinoExternal\Monolog\Utils;

class PsrLogMessageProcessor implements ProcessorInterface
{
    const SIMPLE_DATE = "Y-m-d\\TH:i:s.uP";
    
    private $dateFormat;
    
    private $removeUsedContextFields;
    /**
     * @param string|null $dateFormat
     * @param bool $removeUsedContextFields
     */
    public function __construct($dateFormat = null, $removeUsedContextFields = \false)
    {
        $this->dateFormat = $dateFormat;
        $this->removeUsedContextFields = $removeUsedContextFields;
    }
    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        if (\false === strpos($record['message'], '{')) {
            return $record;
        }
        $replacements = array();
        foreach ($record['context'] as $key => $val) {
            $placeholder = '{' . $key . '}';
            if (strpos($record['message'], $placeholder) === \false) {
                continue;
            }
            if (is_null($val) || is_scalar($val) || is_object($val) && method_exists($val, "__toString")) {
                $replacements[$placeholder] = $val;
            } elseif ($val instanceof \DateTime) {
                $replacements[$placeholder] = $val->format($this->dateFormat ?: static::SIMPLE_DATE);
            } elseif (is_object($val)) {
                $replacements[$placeholder] = '[object ' . Utils::getClass($val) . ']';
            } elseif (is_array($val)) {
                $replacements[$placeholder] = 'array' . Utils::jsonEncode($val, null, \true);
            } else {
                $replacements[$placeholder] = '[' . gettype($val) . ']';
            }
            if ($this->removeUsedContextFields) {
                unset($record['context'][$key]);
            }
        }
        $record['message'] = strtr($record['message'], $replacements);
        return $record;
    }
}
