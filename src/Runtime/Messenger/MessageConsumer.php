<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime\Messenger;

use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\MessageConsumerException;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\MessageConsumerTimeoutException;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessRunnerInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use function microtime;
use function usleep;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

final class MessageConsumer implements MessageConsumerInterface
{
    public function __construct(
        private ProcessRunnerInterface $runner,
        private MessageCounterInterface $counter,
        private float $timeoutSeconds = 30.0,
    ) {
    }

    public function consume(string $receiver): void
    {
        $deadline = microtime(true) + $this->timeoutSeconds;
        $emptyRounds = 0;

        while (microtime(true) < $deadline) {
            $pending = $this->counter->count($receiver);

            if ($pending === 0) {
                ++$emptyRounds;

                if ($emptyRounds >= 2) {
                    return;
                }

                usleep(200_000);
                continue;
            }

            $emptyRounds = 0;

            $output = new BufferedOutput();
            $result = $this->runner->run(
                [
                    PHP_BINARY,
                    'bin' . DIRECTORY_SEPARATOR . 'console',
                    'messenger:consume',
                    $receiver,
                    '--limit=1',
                    '--no-interaction',
                    '--quiet',
                    '--no-ansi',
                ],
                Application::getRootDir(),
                $output,
            );

            if ($result === false) {
                throw new MessageConsumerException($receiver, $output->fetch());
            }
        }

        throw new MessageConsumerTimeoutException($receiver);
    }
}
