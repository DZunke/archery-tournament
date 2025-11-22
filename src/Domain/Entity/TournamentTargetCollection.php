<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use ArrayIterator;
use Countable;
use IteratorAggregate;

use function count;

/** @implements IteratorAggregate<int,TournamentTarget> */
final class TournamentTargetCollection implements IteratorAggregate, Countable
{
    /** @param list<TournamentTarget> $targets */
    public function __construct(private array $targets = [])
    {
    }

    public function add(TournamentTarget $target): void
    {
        $this->targets[] = $target;
    }

    /** @return ArrayIterator<int,TournamentTarget> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->targets);
    }

    public function count(): int
    {
        return count($this->targets);
    }
}
