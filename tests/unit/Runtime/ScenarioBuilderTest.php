<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Stateforge\Scenario\Symfony\Runtime\Exception\ScenarioUnknownException;
use Stateforge\Scenario\Symfony\Runtime\Exception\WrongScenarioSubclassException;
use Stateforge\Scenario\Symfony\Runtime\ScenarioBuilder;
use Stateforge\Scenario\Symfony\Scenario;
use Stateforge\Scenario\Symfony\Tests\Files\ValidScenario;
use stdClass;

#[CoversClass(ScenarioBuilder::class)]
#[UsesClass(ScenarioUnknownException::class)]
#[UsesClass(WrongScenarioSubclassException::class)]
#[Group('runtime')]
#[Small]
final class ScenarioBuilderTest extends TestCase
{
    public function testBuildReturnsScenarioFromContainer(): void
    {
        $scenario = new ValidScenario();

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(ValidScenario::class)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with(ValidScenario::class)
            ->willReturn($scenario);

        self::assertSame($scenario, new ScenarioBuilder($container)->build(ValidScenario::class));
    }

    public function testBuildThrowsWhenScenarioMissing(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(ValidScenario::class)
            ->willReturn(false);
        $container->expects($this->never())
            ->method('get');

        $this->expectException(ScenarioUnknownException::class);
        $this->expectExceptionMessage(ValidScenario::class . ' was not found');

        new ScenarioBuilder($container)->build(ValidScenario::class);
    }

    public function testBuildThrowsWhenScenarioIsWrongSubclass(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(ValidScenario::class)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('get')
            ->with(ValidScenario::class)
            ->willReturn(new stdClass());

        $this->expectException(WrongScenarioSubclassException::class);
        $this->expectExceptionMessage(ValidScenario::class . ' is not from type ' . Scenario::class);

        new ScenarioBuilder($container)->build(ValidScenario::class);
    }
}
