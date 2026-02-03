<?php

declare(strict_types=1);

namespace App\Presentation\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TargetTypeExtension extends AbstractExtension
{
    private const array LABELS = [
        'animal_group_1' => 'Group 1',
        'animal_group_2' => 'Group 2',
        'animal_group_3' => 'Group 3',
        'animal_group_4' => 'Group 4',
    ];

    private const array ICONS = [
        'animal_group_1' => 'ph:number-one-fill',
        'animal_group_2' => 'ph:number-two-fill',
        'animal_group_3' => 'ph:number-three-fill',
        'animal_group_4' => 'ph:number-four-fill',
    ];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('target_type_label', [$this, 'label']),
            new TwigFunction('target_type_icon', [$this, 'icon']),
        ];
    }

    public function label(string $value): string
    {
        return self::LABELS[$value] ?? $value;
    }

    public function icon(string $value): string
    {
        return self::ICONS[$value] ?? 'lucide:circle';
    }
}
