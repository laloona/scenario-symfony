<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Runtime\CompilerPass;

use Scenario\Symfony\Runtime\Metadata\Handler\RefreshDatabaseHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class RegisterDatabaseHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(RefreshDatabaseHandler::class) === false) {
            $container->setDefinition(
                RefreshDatabaseHandler::class,
                new Definition(RefreshDatabaseHandler::class),
            );
        }

        $container->getDefinition(RefreshDatabaseHandler::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setPublic(false);

        $container->setAlias('scenario.refresh_database_handler', RefreshDatabaseHandler::class)->setPublic(true);
    }
}
