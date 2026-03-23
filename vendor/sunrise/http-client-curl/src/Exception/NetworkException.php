<?php

declare (strict_types=1);
/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Fenric <anatoly@fenric.ru>
 * @copyright Copyright (c) 2018, Anatoly Fenric
 * @license https://github.com/sunrise-php/http-client-curl/blob/master/LICENSE
 * @link https://github.com/sunrise-php/http-client-curl
 */
namespace ComfinoExternal\Sunrise\Http\Client\Curl\Exception;

use ComfinoExternal\Psr\Http\Client\NetworkExceptionInterface;
use ComfinoExternal\Psr\Http\Message\RequestInterface;
use Throwable;

class NetworkException extends ClientException implements NetworkExceptionInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @param RequestInterface $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(RequestInterface $request, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->request = $request;
        parent::__construct($message, $code, $previous);
    }
    
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
