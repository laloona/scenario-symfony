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
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\MessageConsumerException;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\ReceiverCounterException;
use Stateforge\Scenario\Symfony\Runtime\Messenger\MessageCounter;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessRunnerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

#[CoversClass(MessageCounter::class)]
#[CoversClass(MessageConsumerException::class)]
#[CoversClass(ReceiverCounterException::class)]
#[Group('runtime')]
#[Small]
final class MessageCounterTest extends TestCase
{
    public function testReturnsMessageCount(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects(self::once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    'bin' . DIRECTORY_SEPARATOR . 'console',
                    'messenger:stats',
                    'async',
                    '--no-interaction',
                    '--no-ansi',
                ],
                self::isString(),
                [
                    'APP_DEBUG' => '0',
                ],
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturnCallback(static function (array $arguments, string $directory, ?array $env, OutputInterface $output): bool {
                $output->writeln("Transport Name        Messages\n-----------------------------\nasync                 5");

                return true;
            });

        self::assertSame(5, (new MessageCounter($runner))->count('async'));
    }

    public function testThrowsWhenStatsCommandFails(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects(self::once())
            ->method('run')
            ->willReturnCallback(static function (array $arguments, string $directory, ?array $env, OutputInterface $output): bool {
                $output->writeln('transport failure');

                return false;
            });

        $this->expectException(MessageConsumerException::class);
        $this->expectExceptionMessage('messenger consumer for receiver [async] failed: transport failure');

        (new MessageCounter($runner))->count('async');
    }

    public function testThrowsWhenReceiverCountCannotBeParsedFromStatsOutput(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects(self::once())
            ->method('run')
            ->willReturnCallback(static function (array $arguments, string $directory, ?array $env, OutputInterface $output): bool {
                $output->writeln("Transport Name        Messages\n-----------------------------\nother                 5");

                return true;
            });

        $this->expectException(ReceiverCounterException::class);
        $this->expectExceptionMessage(
            'could not determine the number of pending messages for receiver "async" from messenger:stats output',
        );

        (new MessageCounter($runner))->count('async');
    }
}
