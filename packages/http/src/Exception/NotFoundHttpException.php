<?php

declare(strict_types=1);

namespace Docile\Http\Exception;

class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = 'Not Found', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous, 404);
    }
}