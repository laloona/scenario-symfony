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
use Stateforge\Scenario\Symfony\Runtime\ProcessFactory;
use Stateforge\Scenario\Symfony\Runtime\ProcessFactoryInterface;
use Stateforge\Scenario\Symfony\Runtime\ProcessRunner;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[CoversClass(ProcessRunner::class)]
#[UsesClass(ProcessFactory::class)]
#[Group('runtime')]
#[Small]
final class ProcessRunnerTest extends TestCase
{
    public function testRunWritesProcessOutputAndReturnsSuccess(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::once())
            ->method('write')
            ->with('process output');

        $process = $this->createMock(Process::class);
        $process->expects(self::once())
            ->method('setTimeout')
            ->with(null);
        $process->expects(self::once())
            ->method('run')
            ->willReturnCallback(function (callable $callback): int {
                $callback('out', 'process output');

                return 0;
            });
        $process->expects(self::once())
            ->method('isSuccessful')
            ->willReturn(true);

        $factory = $this->createMock(ProcessFactoryInterface::class);
        $factory->expects(self::once())
            ->method('create')
            ->with(['php', 'bin/console'], '/project')
            ->willReturn($process);

        self::assertTrue((new ProcessRunner($factory))->run(['php', 'bin/console'], '/project', $output));
    }

    public function testRunReturnsFalseWhenProcessFails(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::never())
            ->method('write');

        $process = $this->createMock(Process::class);
        $process->expects(self::once())
            ->method('setTimeout')
            ->with(null);
        $process->expects(self::once())
            ->method('run')
            ->willReturn(1);
        $process->expects(self::once())
            ->method('isSuccessful')
            ->willReturn(false);

        $factory = $this->createMock(ProcessFactoryInterface::class);
        $factory->expects(self::once())
            ->method('create')
            ->with(['php', 'bin/console'], '/project')
            ->willReturn($process);

        self::assertFalse((new ProcessRunner($factory))->run(['php', 'bin/console'], '/project', $output));
    }
}
