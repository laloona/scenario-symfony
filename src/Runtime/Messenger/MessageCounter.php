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
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\ReceiverCounterException;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessRunnerInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function str_contains;
use function str_replace;
use function trim;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

final class MessageCounter implements MessageCounterInterface
{
    public function __construct(private ProcessRunnerInterface $runner)
    {
    }

    public function count(string $receiver): int
    {
        $output = new BufferedOutput();
        $result = $this->runner->run(
            [
                PHP_BINARY,
                'bin' . DIRECTORY_SEPARATOR . 'console',
                'messenger:stats',
                $receiver,
                '--no-interaction',
                '--no-ansi',
            ],
            Application::getRootDir(),
            $output,
        );
        $content = $output->fetch();

        if ($result === false) {
            throw new MessageConsumerException($receiver, $content);
        }

        return $this->parseCount($receiver, $content);
    }

    private function parseCount(string $receiver, string $output): int
    {
        /** @var list<string> $splitted */
        $splitted = preg_split("/\r\n|\n|\r/", $output);
        foreach ($splitted as $line) {
            $normalized = $this->normalizeLine($line);

            if ($normalized === ''
                || str_contains($normalized, 'Transport') === true
                || preg_match('/^-+$/', $normalized) === 1) {
                continue;
            }

            $matches = [];
            if (preg_match('/^' . preg_quote($receiver, '/') . '\s+(\d+)$/', $normalized, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        throw new ReceiverCounterException($receiver);
    }

    private function normalizeLine(string $line): string
    {
        $line = trim(str_replace(
            ['│', '┃', '║', '|', "\t"],
            ' ',
            $line,
        ));

        /** @var string $line */
        $line = preg_replace('/\s+/', ' ', $line);
        return $line;
    }
}
