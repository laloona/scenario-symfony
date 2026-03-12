<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Tests\Unit\Runtime\CompilerPass;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Scenario\Symfony\Runtime\CompilerPass\RegisterScenarioPass;
use Scenario\Symfony\Tests\Files\ValidScenario;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(RegisterScenarioPass::class)]
#[Group('runtime')]
#[Small]
final class RegisterScenarioPassTest extends TestCase
{
    public function testProcessRegistersScenarioDefinitionsWhenMissing(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('scenario.definitions', [ValidScenario::class]);

        (new RegisterScenarioPass())->process($container);

        self::assertTrue($container->hasDefinition(ValidScenario::class));

        $definition = $container->getDefinition(ValidScenario::class);
        self::assertTrue($definition->isAutowired());
        self::assertTrue($definition->isAutoconfigured());
        self::assertTrue($definition->isPublic());
        self::assertArrayHasKey('scenario.interface', $definition->getTags());
    }

    public function testProcessUpdatesExistingDefinition(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('scenario.definitions', [ValidScenario::class]);

        $definition = new Definition(ValidScenario::class);
        $definition->setAutowired(false);
        $definition->setAutoconfigured(false);
        $definition->setPublic(false);
        $container->setDefinition(ValidScenario::class, $definition);

        (new RegisterScenarioPass())->process($container);

        $updatedDefinition = $container->getDefinition(ValidScenario::class);
        self::assertTrue($updatedDefinition->isAutowired());
        self::assertTrue($updatedDefinition->isAutoconfigured());
        self::assertTrue($updatedDefinition->isPublic());
        self::assertArrayHasKey('scenario.interface', $updatedDefinition->getTags());
    }

    public function testProcessReturnsEarlyWhenDefinitionsParameterIsNotArray(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('scenario.definitions', 'not-an-array');

        (new RegisterScenarioPass())->process($container);

        self::assertFalse($container->hasDefinition(ValidScenario::class));
    }
}
