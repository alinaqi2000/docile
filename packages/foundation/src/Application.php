<?php

declare(strict_types=1);

namespace Docile\Foundation;

use Docile\Console\Kernel as ConsoleKernel;
use Docile\Console\Output as ConsoleOutput;
use Docile\Container\ContainerInterface;
use Docile\Foundation\Exception\FoundationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * The application composition root.
 *
 * Owns the DI container and the exception handler.  All incoming traffic
 * (HTTP or console) is funnelled through here.
 */
final class Application
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ExceptionHandler $exceptionHandler = new ExceptionHandler(),
    ) {}

    /**
     * Run a bootstrapper against this application.
     */
    public function bootstrap(BootstrapperInterface $bootstrapper): void
    {
        $bootstrapper->bootstrap($this);
    }

    /**
     * Access the underlying DI container.
     */
    public function container(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Resolve a PSR-15 request handler from the container and dispatch the request.
     * On any exception, delegates to ExceptionHandler::handleHttp().
     *
     * $handlerClass should be a class-string that resolves to a RequestHandlerInterface.
     */
    public function handleHttp(
        ServerRequestInterface $request,
        string $handlerClass,
    ): ResponseInterface {
        try {
            if (!$this->container->has($handlerClass)) {
                throw FoundationException::handlerNotFound($handlerClass);
            }

            $handler = $this->container->get($handlerClass);

            if (!$handler instanceof RequestHandlerInterface) {
                throw FoundationException::invalidHandler($handlerClass);
            }

            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->exceptionHandler->handleHttp($e);
        }
    }

    /**
     * Dispatch console argv to Docile\Console\Kernel.
     * On any exception, delegates to ExceptionHandler::handleConsole().
     *
     * @param array<int, string> $argv
     */
    public function handleConsole(array $argv): int
    {
        $output = new ConsoleOutput();

        try {
            if (!$this->container->has(ConsoleKernel::class)) {
                throw FoundationException::consoleKernelNotFound();
            }

            $kernel = $this->container->get(ConsoleKernel::class);

            if (!$kernel instanceof ConsoleKernel) {
                throw FoundationException::consoleKernelNotFound();
            }

            return $kernel->handle($argv, $output);
        } catch (Throwable $e) {
            $stream = fopen('php://stdout', 'w');

            if ($stream === false) {
                return 1;
            }

            return $this->exceptionHandler->handleConsole($e, $stream);
        }
    }
}
