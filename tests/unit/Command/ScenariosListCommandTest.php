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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Scenario\Symfony\Command\ScenarioListCommand;
use Scenario\Symfony\Runtime\ProcessRunnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(ScenarioListCommand::class)]
#[Group('command')]
#[Small]
final class ScenariosListCommandTest extends TestCase
{
    use ScenarioCommand;

    public function testExecuteRunsScenarioListCommand(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    '/project/vendor/bin/scenario',
                    'list',
                    '--force',
                    '--quiet',
                ],
                '/project',
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $exitCode = new ScenarioListCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        )->run(new ArrayInput([]), new NullOutput());

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteAddsSuiteArgumentWhenProvided(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    '/project/vendor/bin/scenario',
                    'list',
                    '--force',
                    '--quiet',
                    '--suite=api',
                ],
                '/project',
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $exitCode = new ScenarioListCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        )->run(new ArrayInput(['--suite' => 'api']), new NullOutput());

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteReturnsFailureWhenProcessRunnerFails(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->willReturn(false);

        $command = new ScenarioListCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        );

        $exitCode = $command->run(new ArrayInput([]), new NullOutput());

        self::assertSame(Command::FAILURE, $exitCode);
    }
}
