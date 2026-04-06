<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
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
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\MessageConsumerException;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\MessageConsumerTimeoutException;
use Stateforge\Scenario\Symfony\Runtime\Messenger\MessageConsumer;
use Stateforge\Scenario\Symfony\Runtime\Messenger\MessageCounterInterface;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessRunnerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

#[CoversClass(MessageConsumer::class)]
#[UsesClass(MessageConsumerException::class)]
#[UsesClass(MessageConsumerTimeoutException::class)]
#[Group('runtime')]
#[Small]
final class MessageConsumerTest extends TestCase
{
    public function testConsumeRunsMessengerConsumerSuccessfully(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects(self::once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    'bin' . DIRECTORY_SEPARATOR . 'console',
                    'messenger:consume',
                    'async',
                    '--limit=1',
                    '--no-interaction',
                    '--quiet',
                    '--no-ansi',
                ],
                Application::getRootDir(),
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $counter = $this->createMock(MessageCounterInterface::class);
        $counter->expects(self::exactly(3))
            ->method('count')
            ->with('async')
            ->willReturnOnConsecutiveCalls(1, 0, 0);

        (new MessageConsumer($runner, $counter))->consume('async');
    }

    public function testConsumeThrowsTimeoutWhenQueueNeverDrains(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects(self::never())
            ->method('run');

        $counter = $this->createMock(MessageCounterInterface::class);
        $counter->expects(self::never())
            ->method('count');

        $this->expectException(MessageConsumerTimeoutException::class);
        $this->expectExceptionMessage('queue for receiver "stuck" could not be drained before the timeout was reached');

        (new MessageConsumer($runner, $counter, -1.0))->consume('stuck');
    }

    public function testConsumeThrowsWhenProcessRunnerFails(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects(self::once())
            ->method('run')
            ->willReturnCallback(static function (array $arguments, string $directory, OutputInterface $output): bool {
                $output->writeln('transport failure');

                return false;
            });

        $counter = $this->createMock(MessageCounterInterface::class);
        $counter->expects(self::once())
            ->method('count')
            ->with('failed')
            ->willReturn(1);

        $this->expectException(MessageConsumerException::class);
        $this->expectExceptionMessage('messenger consumer for receiver [failed] failed: transport failure');

        (new MessageConsumer($runner, $counter))->consume('failed');
    }
}
