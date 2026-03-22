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

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class ProcessRunner implements ProcessRunnerInterface
{
    public function run(array $arguments, string $directory, OutputInterface $output): bool
    {
        $process = new Process($arguments, $directory);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) use ($output): void {
            $output->write($buffer);
        });

        return $process->isSuccessful();
    }
}
