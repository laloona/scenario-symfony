<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Scenario\Core\Console\Command\Command as ScenarioCommand;
use Scenario\Symfony\Runtime\CommandRunner;
use Scenario\Symfony\Runtime\Exception\CommandRunnerException;
use Scenario\Symfony\Runtime\Exception\CommandRunnerResultException;
use Scenario\Symfony\Tests\Files\DemoCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\HttpKernel\KernelInterface;

#[CoversClass(CommandRunner::class)]
#[UsesClass(CommandRunnerException::class)]
#[UsesClass(CommandRunnerResultException::class)]
#[Group('runtime')]
#[Small]
final class CommandRunnerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setApplication(null);
    }

    public function testExecuteRunsCommandSuccessfully(): void
    {
        $app = $this->createMock(Application::class);
        $app->expects($this->once())
            ->method('find')
            ->with('demo:ok')
            ->willReturn(new DemoCommand(ScenarioCommand::Success->value));

        $this->setApplication($app);

        $runner = new CommandRunner($this->createMock(KernelInterface::class));
        $runner->execute('demo:ok', []);
    }

    public function testExecuteThrowsWhenCommandFails(): void
    {
        $app = $this->createMock(Application::class);
        $app->expects($this->once())
            ->method('find')
            ->with('demo:fail')
            ->willReturn(new DemoCommand(ScenarioCommand::Error->value));

        $this->setApplication($app);

        $runner = new CommandRunner($this->createMock(KernelInterface::class));

        $this->expectException(CommandRunnerResultException::class);
        $this->expectExceptionMessage('Command [demo:fail] failed with exit code: 1');

        $runner->execute('demo:fail', []);
    }

    public function testExecuteWrapsThrownExceptions(): void
    {
        $app = $this->createMock(Application::class);
        $app->expects($this->once())
            ->method('find')
            ->with('demo:explode')
            ->willReturn(new DemoCommand(ScenarioCommand::Success->value, new RuntimeException('boom')));

        $this->setApplication($app);

        $runner = new CommandRunner($this->createMock(KernelInterface::class));

        $this->expectException(CommandRunnerException::class);
        $this->expectExceptionMessage('Command [demo:explode] throwed the following exception: RuntimeException boom');

        $runner->execute('demo:explode', []);
    }

    private function setApplication(?Application $application): void
    {
        $property = (new ReflectionClass(CommandRunner::class))->getProperty('application');
        $property->setAccessible(true);
        $property->setValue(null, $application);
    }
}
