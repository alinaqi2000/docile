<?php

declare(strict_types=1);

namespace App\Exceptions;

use Docile\Foundation\Exceptions\ExceptionHandler;
use Throwable;

final class Handler extends ExceptionHandler
{
    /**
     * A list of exception types that should not be reported.
     *
     * @var list<class-string<\Throwable>>
     */
    protected array $dontReport = [];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render(\Psr\Http\Message\ServerRequestInterface $request, Throwable $e): \Psr\Http\Message\ResponseInterface
    {
        return parent::render($request, $e);
    }
}
