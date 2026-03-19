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
use Symfony\Component\Process\Process;

final class MessageConsumer
{
    public function consume(string $receiver): void
    {
        $process = new Process([
            PHP_BINARY,
            'bin' . DIRECTORY_SEPARATOR . 'console',
            'messenger:consume',
            $receiver,
            '--sleep=0',
            '--time-limit=1',
            '--no-interaction',
            '--quiet',
            '--no-ansi',
        ], Application::getRootDir());
        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful() === false) {
            throw new MessageConsumerException($receiver, $process->getErrorOutput());
        }
    }
}
