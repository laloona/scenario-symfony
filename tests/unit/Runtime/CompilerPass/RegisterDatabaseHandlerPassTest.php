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
use Scenario\Symfony\Runtime\CompilerPass\RegisterDatabaseHandlerPass;
use Scenario\Symfony\Runtime\Metadata\Handler\RefreshDatabaseHandler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(RegisterDatabaseHandlerPass::class)]
#[Group('runtime')]
#[Small]
final class RegisterDatabaseHandlerPassTest extends TestCase
{
    public function testProcessRegistersDefinitionAndAlias(): void
    {
        $container = new ContainerBuilder();
        (new RegisterDatabaseHandlerPass())->process($container);

        self::assertTrue($container->hasDefinition(RefreshDatabaseHandler::class));
        self::assertTrue($container->hasAlias('scenario.refresh_database_handler'));

        $definition = $container->getDefinition(RefreshDatabaseHandler::class);
        self::assertTrue($definition->isAutowired());
        self::assertTrue($definition->isAutoconfigured());
        self::assertFalse($definition->isPublic());

        $alias = $container->getAlias('scenario.refresh_database_handler');
        self::assertSame(RefreshDatabaseHandler::class, (string) $alias);
        self::assertTrue($alias->isPublic());
    }

    public function testProcessUpdatesExistingDefinition(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(RefreshDatabaseHandler::class);
        $definition->setAutowired(false);
        $definition->setAutoconfigured(false);
        $definition->setPublic(true);
        $container->setDefinition(RefreshDatabaseHandler::class, $definition);

        (new RegisterDatabaseHandlerPass())->process($container);

        $updated = $container->getDefinition(RefreshDatabaseHandler::class);
        self::assertTrue($updated->isAutowired());
        self::assertTrue($updated->isAutoconfigured());
        self::assertFalse($updated->isPublic());
    }
}
