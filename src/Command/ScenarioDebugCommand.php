<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class ScenarioDebugCommand extends ScenarioCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:debug')
            ->setDescription('Debugs a given scenario or Unit test. - should only be used for dev/test')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $process = new Process([
            PHP_BINARY,
            $this->getCliPath(),
            'debug',
            '--force',
            '--quiet',
        ], $this->getKernel()->getProjectDir());

        $process->setTimeout(null);
        $process->setTty(Process::isTtySupported());
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        return $process->isSuccessful() === true
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
