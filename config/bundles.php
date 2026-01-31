<?php

declare(strict_types=1);

use Symfony\Bundle\DebugBundle\DebugBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    // Backend Packages
    FrameworkBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],

    // Development Packages
    DebugBundle::class => ['dev' => true],
];
