<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\DependencyInjection\Configuration;
use Stateforge\Scenario\Symfony\DependencyInjection\ScenarioSymfonyExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(ScenarioSymfonyExtension::class)]
#[UsesClass(Configuration::class)]
#[Group('dependency-injection')]
#[Small]
final class ScenarioSymfonyExtensionTest extends TestCase
{
    public function testLoadSetsParametersAndLoadsServicesWhenEnabledAndEnvAllowed(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new ScenarioSymfonyExtension();
        $extension->load([[
            'enabled' => true,
            'allowed_envs' => ['test'],
        ]], $container);

        self::assertSame(true, $container->getParameter('scenario.enabled'));
        self::assertSame(['test'], $container->getParameter('scenario.allowed_envs'));
    }

    public function testLoadSkipsServicesWhenDisabled(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension = new ScenarioSymfonyExtension();
        $extension->load([[
            'enabled' => false,
        ]], $container);

        self::assertSame(false, $container->getParameter('scenario.enabled'));
        self::assertSame(['dev', 'test'], $container->getParameter('scenario.allowed_envs'));
    }

    public function testLoadSkipsServicesWhenEnvironmentNotAllowed(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new ScenarioSymfonyExtension();
        $extension->load([[
            'enabled' => true,
            'allowed_envs' => ['dev'],
        ]], $container);

        self::assertSame(true, $container->getParameter('scenario.enabled'));
        self::assertSame(['dev'], $container->getParameter('scenario.allowed_envs'));
    }
}
