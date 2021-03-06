<?php

declare(strict_types=1);

namespace FluencePrototype\Auth;

use Exception;
use FluencePrototype\Http\Messages\Response\StatusCodes;
use JetBrains\PhpStorm\Pure;
use Throwable;

/**
 * Class ForbiddenException
 * @package FluencePrototype\Auth
 */
class ForbiddenException extends Exception
{

    /**
     * MethodNotAllowedException constructor.
     * @param string $message
     * @param Throwable|null $previous
     */
    #[Pure] public function __construct(string $message = '', Throwable $previous = null)
    {
        parent::__construct($message, StatusCodes::FORBIDDEN, $previous);
    }

}