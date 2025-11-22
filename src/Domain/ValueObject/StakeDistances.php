<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use ArrayIterator;
use IteratorAggregate;
use Webmozart\Assert\Assert;

use function count;
use function sprintf;

/** @implements IteratorAggregate<string,int> */
final class StakeDistances implements IteratorAggregate
{
    /** @param array<string,int> $distances */
    public function __construct(private readonly array $distances)
    {
        Assert::notEmpty($this->distances, 'Stake distances must not be empty.');
        foreach ($this->distances as $distance) {
            Assert::greaterThanEq($distance, 0, 'Stake distance must be non-negative.');
        }
    }

    /** @return array<string,int> */
    public function all(): array
    {
        return $this->distances;
    }

    public function has(string $stake): bool
    {
        return isset($this->distances[$stake]);
    }

    public function get(string $stake): int
    {
        Assert::true($this->has($stake), sprintf('Stake "%s" is not defined.', $stake));

        return $this->distances[$stake];
    }

    /** @return ArrayIterator<string,int> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->distances);
    }

    public function count(): int
    {
        return count($this->distances);
    }
}
