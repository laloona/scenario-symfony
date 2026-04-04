<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Runtime\CompilerPass;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Runtime\CompilerPass\RegisterBuilderPass;
use Stateforge\Scenario\Symfony\Runtime\ScenarioBuilder;
use Stateforge\Scenario\Symfony\Tests\Files\ValidScenario;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(RegisterBuilderPass::class)]
#[Group('runtime')]
#[Small]
final class RegisterBuilderPassTest extends TestCase
{
    public function testProcessRegistersBuilderAndAlias(): void
    {
        $container = new ContainerBuilder();

        $definition = new Definition(ValidScenario::class);
        $definition->addTag('scenario.interface');
        $container->setDefinition(ValidScenario::class, $definition);

        (new RegisterBuilderPass())->process($container);

        self::assertTrue($container->hasDefinition(ScenarioBuilder::class));
        self::assertTrue($container->hasAlias('scenario.builder'));

        $builderDefinition = $container->getDefinition(ScenarioBuilder::class);
        self::assertFalse($builderDefinition->isAutowired());
        self::assertFalse($builderDefinition->isAutoconfigured());
        self::assertFalse($builderDefinition->isPublic());
        self::assertNotNull($builderDefinition->getArgument('$locator'));

        $alias = $container->getAlias('scenario.builder');
        self::assertSame(ScenarioBuilder::class, (string) $alias);
        self::assertTrue($alias->isPublic());
    }

    public function testProcessUpdatesExistingDefinition(): void
    {
        $container = new ContainerBuilder();

        $builderDefinition = new Definition(ScenarioBuilder::class);
        $builderDefinition->setAutowired(true);
        $builderDefinition->setAutoconfigured(true);
        $builderDefinition->setPublic(true);
        $container->setDefinition(ScenarioBuilder::class, $builderDefinition);

        (new RegisterBuilderPass())->process($container);

        $updated = $container->getDefinition(ScenarioBuilder::class);
        self::assertFalse($updated->isAutowired());
        self::assertFalse($updated->isAutoconfigured());
        self::assertFalse($updated->isPublic());
        self::assertNotNull($updated->getArgument('$locator'));
    }
}
