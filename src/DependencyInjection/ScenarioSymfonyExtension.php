<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use function in_array;
use function is_string;

final class ScenarioSymfonyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        /** @var array{enabled: bool, allowed_envs: list<string>} $config */
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('scenario.enabled', $config['enabled']);
        $container->setParameter('scenario.allowed_envs', $config['allowed_envs']);

        $env = $container->getParameter('kernel.environment');

        if ($config['enabled'] !== true
            || is_string($env) === false
            || in_array($env, $config['allowed_envs'], true) === false) {
            return;
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }
}
