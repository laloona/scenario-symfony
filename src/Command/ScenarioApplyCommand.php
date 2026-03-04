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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class ScenarioApplyCommand extends ScenarioCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:apply')
            ->setDescription('Applies a given scenario, use --up or --down to choose how the scenario should be applied - should only be used for dev/test')
            ->addArgument('scenario')
            ->addOption('up', null, InputOption::VALUE_NONE, 'applies up method')
            ->addOption('down', null, InputOption::VALUE_NONE, 'applies down method')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $arguments = [];
        if ($input->getArgument('scenario') !== null) {
            $arguments[] = $input->getArgument('scenario');
        }
        if ($input->getOption('up') === true) {
            $arguments[] = '--up';
        }
        if ($input->getOption('down') === true) {
            $arguments[] = '--down';
        }
        $arguments[] = '--force';
        $arguments[] = '--quiet';

        $process = new Process([
            PHP_BINARY,
            $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario',
            'apply',
            ...$arguments,
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
