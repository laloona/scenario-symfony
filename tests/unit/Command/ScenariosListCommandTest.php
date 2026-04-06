<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Command\ScenarioListCommand;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessRunnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

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
                    '/project' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario',
                    'list',
                    '--force',
                    '--quiet',
                ],
                '/project',
                null,
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $exitCode = (new ScenarioListCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        ))->run(new ArrayInput([]), new NullOutput());

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
                    '/project' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario',
                    'list',
                    '--force',
                    '--quiet',
                    '--suite=api',
                ],
                '/project',
                null,
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $exitCode = (new ScenarioListCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        ))->run(new ArrayInput(['--suite' => 'api']), new NullOutput());

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
