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

use Symfony\Component\Process\Process;

final class ProcessFactory implements ProcessFactoryInterface
{
    /**
     * @param list<string> $arguments
     */
    public function create(array $arguments, string $directory): Process
    {
        $process = new Process($arguments, $directory);
        $process->setTty(Process::isTtySupported());

        return $process;
    }
}
