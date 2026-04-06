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
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\MessageConsumerMaxAttemptsException;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessRunnerInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use function usleep;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

final class MessageConsumer implements MessageConsumerInterface
{
    public function __construct(
        private ProcessRunnerInterface $runner,
        private MessageCounterInterface $counter,
        private int $maxAttempts,
    ) {
    }

    public function consume(string $receiver): void
    {
        $attempts = 0;
        while ($this->counter->count($receiver) > 0) {
            if ($attempts > $this->maxAttempts) {
                throw new MessageConsumerMaxAttemptsException($attempts, $receiver);
            }
            $attempts++;

            $output = new BufferedOutput();
            $result = $this->runner->run(
                [
                    PHP_BINARY,
                    'bin' . DIRECTORY_SEPARATOR . 'console',
                    'messenger:consume',
                    $receiver,
                    '---sleep=0',
                    '--time-limit=2',
                    '--no-interaction',
                    '--quiet',
                    '--no-ansi',
                ],
                Application::getRootDir(),
                [
                    'APP_DEBUG' => '0',
                ],
                $output,
            );

            if ($result === false) {
                throw new MessageConsumerException($receiver, $output->fetch());
            }
            usleep(200_000);
        }
    }
}
