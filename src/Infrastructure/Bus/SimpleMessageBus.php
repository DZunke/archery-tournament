<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus;

use App\Application\Bus\CommandBus;
use App\Application\Bus\QueryBus;
use App\Application\Command\CommandResult;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function class_exists;
use function is_callable;
use function sprintf;

final readonly class SimpleMessageBus implements CommandBus, QueryBus
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function dispatch(object $command): CommandResult
    {
        $handler = $this->resolveHandler($command);
        $result  = $handler($command);

        if (! $result instanceof CommandResult) {
            throw new RuntimeException('Command handler must return a CommandResult.');
        }

        return $result;
    }

    public function ask(object $query): mixed
    {
        $handler = $this->resolveHandler($query);

        return $handler($query);
    }

    private function resolveHandler(object $message): callable
    {
        $handlerClass = $message::class . 'Handler';

        if (! class_exists($handlerClass)) {
            throw new RuntimeException(sprintf('Handler class %s not found.', $handlerClass));
        }

        if (! $this->container->has($handlerClass)) {
            throw new RuntimeException(sprintf('Handler %s is not registered in the container.', $handlerClass));
        }

        $handler = $this->container->get($handlerClass);

        if (! is_callable($handler)) {
            throw new RuntimeException(sprintf('Handler %s is not invokable.', $handlerClass));
        }

        return $handler;
    }
}
