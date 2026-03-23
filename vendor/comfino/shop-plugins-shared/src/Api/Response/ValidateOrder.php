<?php

declare(strict_types=1);

namespace Comfino\Api\Response;

use Comfino\Api\Exception\RequestValidationError;
use Comfino\Api\HttpErrorExceptionInterface;
use Comfino\Api\Request;
use Comfino\Api\SerializerInterface;
use ComfinoExternal\Psr\Http\Client\ClientExceptionInterface;
use ComfinoExternal\Psr\Http\Client\NetworkExceptionInterface;
use ComfinoExternal\Psr\Http\Message\ResponseInterface;

class ValidateOrder extends Base
{
    public $trackId;

    public $success;

    public $httpStatusCode;

    public $errors;

    public $isNetworkError;

    public $errorCode;

    public function __construct(Request $request, ?ResponseInterface $response, SerializerInterface $serializer, ?\Throwable $exception = null)
    {
        parent::__construct($request, $response, $serializer, $exception);

        $this->trackId = ($this->headers['Comfino-Track-Id'] ?? '');
        $this->success = ($exception === null);

        $httpStatusCode = (($nullsafeVariable1 = $response) ? $nullsafeVariable1->getStatusCode() : null) ?? 0;
        $errors = [];
        $isNetworkError = false;
        $errorCode = 0;

        if ($exception !== null) {
            if ($exception instanceof HttpErrorExceptionInterface) {
                $httpStatusCode = $exception->getStatusCode();

                if ($exception instanceof RequestValidationError) {
                    if (is_array($deserializedResponseBody = $exception->getDeserializedResponseBody())) {
                        if (isset($deserializedResponseBody['errors'])) {
                            $errors = $deserializedResponseBody['errors'];
                        } elseif (isset($deserializedResponseBody['message'])) {
                            $errors = [$deserializedResponseBody['message']];
                        } elseif ($exception->getCode() >= 400) {
                            $errors = $deserializedResponseBody;
                        } else {
                            $errors = [$exception->getMessage()];
                        }
                    } else {
                        $errors = [$exception->getMessage()];
                    }
                }
            } elseif ($exception instanceof ClientExceptionInterface) {
                $errors = [$exception->getMessage()];
                $errorCode = $exception->getCode();

                if ($exception instanceof NetworkExceptionInterface) {
                    $isNetworkError = true;
                }
            } else {
                $errors = [$exception->getMessage()];
                $errorCode = $exception->getCode();
            }
        }

        $this->httpStatusCode = $httpStatusCode;
        $this->errors = $errors;
        $this->isNetworkError = $isNetworkError;
        $this->errorCode = $errorCode;
    }
}
