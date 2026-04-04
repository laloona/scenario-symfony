<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use function is_array;

final class RegisterScenarioPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $scenarios = $container->getParameter('scenario.definitions');
        if (is_array($scenarios) === false) {
            return;
        }

        /** @var array<int, class-string> $scenarios */
        foreach ($scenarios as $scenario) {
            if ($container->hasDefinition($scenario) === false) {
                $container->setDefinition(
                    $scenario,
                    new Definition($scenario),
                );
            }

            $container->getDefinition($scenario)
                ->setAutowired(true)
                ->setAutoconfigured(true)
                ->setPublic(true)
                ->addTag('scenario.interface');
        }
    }
}
