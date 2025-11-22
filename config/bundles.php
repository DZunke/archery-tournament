<?php

declare(strict_types=1);

use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

return [
    // Backend Packages
    FrameworkBundle::class => ['all' => true],

    // Development Packages
    DebugBundle::class => ['dev' => true],
];
