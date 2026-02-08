<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Application\Service\TournamentValidation\Rule\AssignmentReferenceRule;
use App\Application\Service\TournamentValidation\Rule\LaneTargetConsistencyRule;
use App\Application\Service\TournamentValidation\Rule\LaneUniquenessRule;
use App\Application\Service\TournamentValidation\Rule\StakeDistanceRule;
use App\Application\Service\TournamentValidation\Rule\TargetCountRule;
use App\Application\Service\TournamentValidation\Rule\TargetGroupBalanceRule;
use App\Application\Service\TournamentValidation\Rule\TargetUniquenessRule;
use App\Presentation\Controller\Tournament\RulesController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[CoversClass(RulesController::class)]
final class RulesControllerTest extends TestCase
{
    #[Test]
    public function rendersRulesPageWithAllSections(): void
    {
        $validationRules = [
            new AssignmentReferenceRule(),
            new StakeDistanceRule(),
            new TargetUniquenessRule(),
            new LaneUniquenessRule(),
            new LaneTargetConsistencyRule(),
            new TargetCountRule(),
            new TargetGroupBalanceRule(),
        ];

        $controller = new RulesController($validationRules);
        $controller->setContainer($this->createTwigContainer());

        $response = $controller();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        self::assertIsString($content);

        // Target Groups section
        self::assertStringContainsString('Target Group 1', $content);
        self::assertStringContainsString('Target Group 4', $content);

        // Rulesets section
        self::assertStringContainsString('DSB 3D', $content);
        self::assertStringContainsString('Freehand', $content);

        // Validation Rules section
        self::assertStringContainsString('Target Count', $content);
        self::assertStringContainsString('Stake Distance', $content);

        // Generator Options section
        self::assertStringContainsString('Include Training-Only', $content);
        self::assertStringContainsString('Randomize Stakes', $content);
    }

    private function createTwigContainer(): ContainerInterface
    {
        $template = <<<'TWIG'
{% for group in targetGroups %}{{ group.label }} {% endfor %}
{% for ruleset in rulesets %}{{ ruleset.name }} {% endfor %}
{% for rule in validationRules %}{{ rule.name }} {% endfor %}
{% for option in generatorOptions %}{{ option.name }} {% endfor %}
TWIG;

        $twig = new Environment(new ArrayLoader(['tournament/rules.html.twig' => $template]));

        return new class ($twig) implements ContainerInterface {
            public function __construct(private readonly Environment $twig)
            {
            }

            public function get(string $id): Environment
            {
                return $this->twig;
            }

            public function has(string $id): bool
            {
                return $id === 'twig';
            }
        };
    }
}
