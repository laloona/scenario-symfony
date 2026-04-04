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
use ReflectionClass;
use RuntimeException;
use Stateforge\Scenario\Symfony\Runtime\CommandRunner;
use Stateforge\Scenario\Symfony\Runtime\Exception\CommandRunnerException;
use Stateforge\Scenario\Symfony\Runtime\Exception\CommandRunnerResultException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
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
        $command = $this->createPartialMock(Command::class, ['execute', 'getNativeDefinition']);
        $command->expects(self::once())
            ->method('execute')
            ->willReturn(Command::SUCCESS);
        $command->expects(self::once())
            ->method('getNativeDefinition')
            ->willReturn(new InputDefinition([]));

        $app = $this->createMock(Application::class);
        $app->expects($this->once())
            ->method('find')
            ->with('demo:ok')
            ->willReturn($command);

        $this->setApplication($app);
        new CommandRunner(self::createStub(KernelInterface::class))
            ->execute('demo:ok', []);
    }

    public function testExecuteThrowsWhenCommandFails(): void
    {
        $command = $this->createPartialMock(Command::class, ['execute', 'getNativeDefinition']);
        $command->expects(self::once())
            ->method('execute')
            ->willReturn(Command::FAILURE);
        $command->expects(self::once())
            ->method('getNativeDefinition')
            ->willReturn(new InputDefinition([]));

        $app = $this->createMock(Application::class);
        $app->expects($this->once())
            ->method('find')
            ->with('demo:fail')
            ->willReturn($command);

        $this->setApplication($app);

        $this->expectException(CommandRunnerResultException::class);
        $this->expectExceptionMessage('Command [demo:fail] failed with exit code: 1');

        new CommandRunner(self::createStub(KernelInterface::class))
            ->execute('demo:fail', []);
    }

    public function testExecuteWrapsThrownExceptions(): void
    {
        $command = $this->createPartialMock(Command::class, ['execute', 'getNativeDefinition']);
        $command->expects(self::once())
            ->method('execute')
            ->willThrowException(new RuntimeException('some error happened.'));
        $command->expects(self::once())
            ->method('getNativeDefinition')
            ->willReturn(new InputDefinition([]));

        $app = $this->createMock(Application::class);
        $app->expects($this->once())
            ->method('find')
            ->with('demo:throw')
            ->willReturn($command);

        $this->setApplication($app);

        $this->expectException(CommandRunnerException::class);
        $this->expectExceptionMessage('Command [demo:throw] throwed the following exception: RuntimeException some error happened.');

        new CommandRunner(self::createStub(KernelInterface::class))
            ->execute('demo:throw', []);
    }

    public function testConstructorKeepsExistingApplicationInstance(): void
    {
        $app = $this->createMock(Application::class);
        $this->setApplication($app);

        new CommandRunner(self::createStub(KernelInterface::class));

        self::assertSame($app, $this->getApplication());
    }

    public function testConstructorInitializesSymfonyApplicationWhenMissing(): void
    {
        self::assertNull($this->getApplication());

        new CommandRunner(self::createStub(KernelInterface::class));

        self::assertInstanceOf(Application::class, $this->getApplication());
    }

    private function setApplication(?Application $application): void
    {
        $property = (new ReflectionClass(CommandRunner::class))->getProperty('application');
        $property->setValue(null, $application);
    }

    private function getApplication(): ?Application
    {
        $property = (new ReflectionClass(CommandRunner::class))->getProperty('application');

        /** @var Application|null $application */
        $application = $property->getValue();

        return $application;
    }
}
