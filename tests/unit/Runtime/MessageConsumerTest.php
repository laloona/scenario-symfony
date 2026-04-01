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
use Scenario\Core\Runtime\Application;
use Scenario\Symfony\Runtime\Exception\MessageConsumerException;
use Scenario\Symfony\Runtime\MessageConsumer;
use Scenario\Symfony\Runtime\ProcessRunnerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

#[CoversClass(MessageConsumer::class)]
#[UsesClass(MessageConsumerException::class)]
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
                    '--sleep=0',
                    '--time-limit=2',
                    '--no-interaction',
                    '--quiet',
                    '--no-ansi',
                ],
                Application::getRootDir(),
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        new MessageConsumer($runner)->consume('async');
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

        $this->expectException(MessageConsumerException::class);
        $this->expectExceptionMessage('Messenger Consumer for receiver [failed] failed: transport failure');

        new MessageConsumer($runner)->consume('failed');
    }
}
