<?php

declare(strict_types=1);

namespace Comfino\Extended\Api\Dto\Plugin;

class ErrorCategory
{
    public const PhpError = 'php_error';
    public const ExceptionTypeError = 'exception_type_error';
    public const ExceptionParseError = 'exception_parse_error';
    public const ExceptionDivision = 'exception_division';
    public const ExceptionArgCount = 'exception_arg_count';
    public const ExceptionLogicError = 'exception_logic_error';
    public const ExceptionError = 'exception_error';
    public const ExceptionGeneric = 'exception_generic';
    public const ExceptionOther = 'exception_other';
    public const ApiError = 'api_error';
    public const ConfigurationError = 'configuration_error';
    public const ValidationError = 'validation_error';
    public const Other = 'other';
}
