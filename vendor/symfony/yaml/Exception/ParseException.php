<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ComfinoExternal\Symfony\Component\Yaml\Exception;

class ParseException extends RuntimeException
{
    private $parsedFile;
    private $parsedLine;
    private $snippet;
    private $rawMessage;
    /**
     * @param string $message
     * @param int $parsedLine
     * @param string|null $snippet
     * @param string|null $parsedFile
     */
    public function __construct(string $message, int $parsedLine = -1, string $snippet = null, string $parsedFile = null, \Throwable $previous = null)
    {
        $this->parsedFile = $parsedFile;
        $this->parsedLine = $parsedLine;
        $this->snippet = $snippet;
        $this->rawMessage = $message;
        $this->updateRepr();
        parent::__construct($this->message, 0, $previous);
    }
    /**
     * @return string
     */
    public function getSnippet()
    {
        return $this->snippet;
    }
    /**
     * @param string $snippet
     */
    public function setSnippet($snippet)
    {
        $this->snippet = $snippet;
        $this->updateRepr();
    }
    /**
     * @return string
     */
    public function getParsedFile()
    {
        return $this->parsedFile;
    }
    /**
     * @param string $parsedFile
     */
    public function setParsedFile($parsedFile)
    {
        $this->parsedFile = $parsedFile;
        $this->updateRepr();
    }
    /**
     * @return int
     */
    public function getParsedLine()
    {
        return $this->parsedLine;
    }
    /**
     * @param int $parsedLine
     */
    public function setParsedLine($parsedLine)
    {
        $this->parsedLine = $parsedLine;
        $this->updateRepr();
    }
    private function updateRepr()
    {
        $this->message = $this->rawMessage;
        $dot = \false;
        if ('.' === substr($this->message, -1)) {
            $this->message = substr($this->message, 0, -1);
            $dot = \true;
        }
        if (null !== $this->parsedFile) {
            $this->message .= sprintf(' in %s', json_encode($this->parsedFile, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
        }
        if ($this->parsedLine >= 0) {
            $this->message .= sprintf(' at line %d', $this->parsedLine);
        }
        if ($this->snippet) {
            $this->message .= sprintf(' (near "%s")', $this->snippet);
        }
        if ($dot) {
            $this->message .= '.';
        }
    }
}
