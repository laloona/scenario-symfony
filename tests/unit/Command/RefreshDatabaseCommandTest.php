<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Tests\Unit\Command;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use Scenario\Symfony\Command\RefreshDatabaseCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(RefreshDatabaseCommand::class)]
#[Group('command')]
#[Small]
final class RefreshDatabaseCommandTest extends TestCase
{
    use ScenarioCommand;

    public function testCommandIsConfigured(): void
    {
        $command = new RefreshDatabaseCommand(
            $this->createMock(ManagerRegistry::class),
            $this->getKernel(),
            $this->getFilesystem(),
        );
        $this->invokeConfigure($command);

        self::assertSame('scenario:migrations:refresh', $command->getName());
        self::assertTrue($command->getDefinition()->hasOption('connection'));
        self::assertTrue($command->getDefinition()->hasOption('configuration'));
    }

    public function testExecuteRunsDoctrineCommandsWithConnectionAndConfiguration(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->with('reporting')
            ->willReturn($this->createMock(Connection::class));

        $dropCommand = $this->createMock(Command::class);
        $dropCommand->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (InputInterface $input, OutputInterface $output): int {
                self::assertInstanceOf(ArrayInput::class, $input);
                self::assertTrue($input->getParameterOption('--force'));
                self::assertTrue($input->getParameterOption('--if-exists'));
                self::assertSame('reporting', $input->getParameterOption('--connection'));

                return Command::SUCCESS;
            });

        $createCommand = $this->createMock(Command::class);
        $createCommand->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (InputInterface $input, OutputInterface $output): int {
                self::assertInstanceOf(ArrayInput::class, $input);
                self::assertSame('reporting', $input->getParameterOption('--connection'));

                return Command::SUCCESS;
            });

        $migrateCommand = $this->createMock(Command::class);
        $migrateCommand->expects($this->once())
            ->method('run')
            ->willReturnCallback(function (InputInterface $input, OutputInterface $output): int {
                self::assertInstanceOf(ArrayInput::class, $input);
                self::assertSame('reporting', $input->getParameterOption('--conn'));
                self::assertSame('migrations.php', $input->getParameterOption('--configuration'));

                return Command::SUCCESS;
            });

        $application = $this->createMock(Application::class);
        $application->expects($this->exactly(3))
            ->method('find')
            ->willReturnMap([
                ['doctrine:database:drop', $dropCommand],
                ['doctrine:database:create', $createCommand],
                ['doctrine:migrations:migrate', $migrateCommand],
            ]);

        $input = new ArrayInput([
            '--connection' => 'reporting',
            '--configuration' => 'migrations.php',
        ]);
        $input->setInteractive(false);

        $command = new RefreshDatabaseCommand(
            $doctrine,
            $this->getKernel(),
            $this->getFilesystem(),
        );
        $command->setApplication($application);

        self::assertSame(Command::SUCCESS, $command->run($input, new NullOutput()));
    }

    public function testExecuteFailsWhenConnectionIsUnknown(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->with('missing')
            ->willThrowException(new InvalidArgumentException('unknown connection'));

        $input = new ArrayInput([
            '--connection' => 'missing',
        ]);
        $input->setInteractive(false);

        $exitCode = new RefreshDatabaseCommand(
            $doctrine,
            $this->getKernel(),
            $this->getFilesystem(),
        )->run($input, new NullOutput());

        self::assertSame(Command::FAILURE, $exitCode);
    }

    public function testExecuteFailsWhenNoConsoleApplicationIsAvailable(): void
    {
        $connection = self::createStub(Connection::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getDefaultConnectionName')->willReturn('default');
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->with('default')
            ->willReturn($connection);

        $input = new ArrayInput([]);
        $input->setInteractive(false);

        $exitCode = new RefreshDatabaseCommand(
            $doctrine,
            $this->getKernel(),
            $this->getFilesystem(),
        )->run($input, new NullOutput());

        self::assertSame(Command::FAILURE, $exitCode);
    }

    public function testExecuteFailsWhenSubCommandReturnsFailure(): void
    {
        $connection = self::createStub(Connection::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getDefaultConnectionName')
            ->willReturn('default');
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->with('default')
            ->willReturn($connection);

        $dropCommand = $this->createMock(Command::class);
        $dropCommand->expects($this->once())
            ->method('run')
            ->willReturn(Command::FAILURE);

        $application = $this->createMock(Application::class);
        $application->expects($this->once())
            ->method('find')
            ->with('doctrine:database:drop')
            ->willReturn($dropCommand);

        $input = new ArrayInput([]);
        $input->setInteractive(false);

        $command = new RefreshDatabaseCommand(
            $doctrine,
            $this->getKernel(),
            $this->getFilesystem(),
        );
        $command->setApplication($application);

        self::assertSame(Command::FAILURE, $command->run($input, new NullOutput()));
    }

    public function testExecuteCancelsRefreshWhenUserDoesNotConfirm(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('app_db');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getDefaultConnectionName')->willReturn('default');
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->with('default')
            ->willReturn($connection);

        $tester = new CommandTester(new RefreshDatabaseCommand(
            $doctrine,
            $this->getKernel(),
            $this->getFilesystem(),
        ));
        $tester->setInputs(['no']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Refresh cancelled!', $tester->getDisplay());
    }

    public function testExecuteShowsErrorForUnknownConnectionInInteractiveMode(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->with('missing')
            ->willThrowException(new InvalidArgumentException('unknown connection'));

        $tester = new CommandTester(new RefreshDatabaseCommand(
            $doctrine,
            $this->getKernel(),
            $this->getFilesystem(),
        ));

        self::assertSame(Command::FAILURE, $tester->execute([
            '--connection' => 'missing',
        ], ['interactive' => true]));
        self::assertStringContainsString('Unknown connection missing', $tester->getDisplay());
    }

    public function testExecuteContinuesAfterSubCommandThrowsInInteractiveMode(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('app_db');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getDefaultConnectionName')->willReturn('default');
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->with('default')
            ->willReturn($connection);

        $throwingCommand = $this->createMock(Command::class);
        $throwingCommand->expects($this->once())
            ->method('run')
            ->willThrowException(new RuntimeException('drop failed'));

        $createCommand = $this->createMock(Command::class);
        $createCommand->expects($this->once())
            ->method('run')
            ->willReturn(Command::SUCCESS);

        $migrateCommand = $this->createMock(Command::class);
        $migrateCommand->expects($this->once())
            ->method('run')
            ->willReturn(Command::SUCCESS);

        $application = $this->createMock(Application::class);
        $application->expects($this->exactly(3))
            ->method('find')
            ->willReturnMap([
                ['doctrine:database:drop', $throwingCommand],
                ['doctrine:database:create', $createCommand],
                ['doctrine:migrations:migrate', $migrateCommand],
            ]);

        $command = new RefreshDatabaseCommand(
            $doctrine,
            $this->getKernel(),
            $this->getFilesystem(),
        );
        $command->setApplication($application);

        $tester = new CommandTester($command);
        $tester->setInputs(['yes']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Command doctrine:database:drop failed: drop failed', $tester->getDisplay());
    }

    private function invokeConfigure(object $command): void
    {
        $method = new ReflectionMethod($command, 'configure');
        $method->setAccessible(true);
        $method->invoke($command);
    }
}
