<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Runtime;

use Scenario\Core\Runtime\Application;
use Scenario\Symfony\Runtime\Exception\MessageConsumerException;
use Symfony\Component\Console\Output\BufferedOutput;

final class MessageConsumer
{
    public function __construct(private ProcessRunnerInterface $runner)
    {
    }

    public function consume(string $receiver): void
    {
        $output = new BufferedOutput();
        $result = $this->runner->run(
            [
                PHP_BINARY,
                'bin' . DIRECTORY_SEPARATOR . 'console',
                'messenger:consume',
                $receiver,
                '--sleep=0',
                '--time-limit=2',
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
}
