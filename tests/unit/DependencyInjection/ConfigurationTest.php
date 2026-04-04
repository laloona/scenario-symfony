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
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

#[CoversClass(Configuration::class)]
#[Group('dependency-injection')]
#[Small]
final class ConfigurationTest extends TestCase
{
    public function testDefaultsAreApplied(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, [[]]);

        self::assertSame(true, $config['enabled']);
        self::assertSame(['dev', 'test'], $config['allowed_envs']);
    }

    public function testConfigOverridesDefaults(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, [[
            'enabled' => false,
            'allowed_envs' => ['prod'],
        ]]);

        self::assertSame(false, $config['enabled']);
        self::assertSame(['prod'], $config['allowed_envs']);
    }
}
