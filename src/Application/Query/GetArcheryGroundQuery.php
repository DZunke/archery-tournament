<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Entity\ArcheryGround;
use App\Tests\Fixtures\AcheryGroundMediumSized;

final readonly class GetArcheryGroundQuery
{
    public function query(): ArcheryGround
    {
        // todo: implement real query for fetching data
        return AcheryGroundMediumSized::create();
    }
}
