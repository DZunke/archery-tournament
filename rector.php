<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitSelfCallRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList as ValueObjectSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        PHPUnitSetList::PHPUNIT_120,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        ValueObjectSetList::EARLY_RETURN,
        ValueObjectSetList::INSTANCEOF,
        ValueObjectSetList::DEAD_CODE,
    ])
    ->withRules([
        PreferPHPUnitSelfCallRector::class,
    ])
    ->withComposerBased(phpunit: true, symfony: true)
    ->withSkip([
        ReadOnlyClassRector::class,
        PreferPHPUnitThisCallRector::class,
    ])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withTypeCoverageLevel(0);
