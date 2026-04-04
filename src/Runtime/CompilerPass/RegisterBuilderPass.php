<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime\CompilerPass;

use Stateforge\Scenario\Symfony\Runtime\ScenarioBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterBuilderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $references = [];
        $tagged = $container->findTaggedServiceIds('scenario.interface');
        foreach ($tagged as $id => $tags) {
            $references[$id] = new Reference($id);
        }

        if ($container->hasDefinition(ScenarioBuilder::class) === false) {
            $container->setDefinition(
                ScenarioBuilder::class,
                new Definition(ScenarioBuilder::class),
            );
        }

        $container->getDefinition(ScenarioBuilder::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setPublic(false)
            ->setArgument('$locator', ServiceLocatorTagPass::register($container, $references));

        $container->setAlias('scenario.builder', ScenarioBuilder::class)->setPublic(true);
    }
}
