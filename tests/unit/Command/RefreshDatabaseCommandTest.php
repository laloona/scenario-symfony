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
use Scenario\Symfony\Command\RefreshDatabaseCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(RefreshDatabaseCommand::class)]
#[Group('command')]
#[Small]
final class RefreshDatabaseCommandTest extends TestCase
{
    use ScenarioCommand;

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
}
