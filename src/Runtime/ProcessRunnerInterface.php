<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime;

use Symfony\Component\Console\Output\OutputInterface;

interface ProcessRunnerInterface
{
    /**
     * @param list<string> $arguments
     */
    public function run(array $arguments, string $directory, OutputInterface $output): bool;
}
