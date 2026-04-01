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

use Scenario\Symfony\Runtime\ProcessRunnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use function is_string;
use const PHP_BINARY;

final class ScenarioListCommand extends ScenarioCommand
{
    public function __construct(
        private ProcessRunnerInterface $processRunner,
        KernelInterface $kernel,
        Filesystem $filesystem,
    ) {
        parent::__construct($kernel, $filesystem);
    }

    protected function configure(): void
    {
        $this
            ->setName('scenario:list')
            ->setDescription('List all available scenarios, use --suite="name of you suite" if you want to see just one suite. - should only be used for dev/test')
            ->addOption('suite', null, InputOption::VALUE_REQUIRED, 'fiters to given suite')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $arguments = [
            PHP_BINARY,
            $this->getCliPath(),
            'list',
            '--force',
            '--quiet',
        ];

        $suite = $input->getOption('suite');
        if (is_string($suite) === true && $suite !== '') {
            $arguments[] = '--suite=' . $suite;
        }

        return $this->processRunner->run($arguments, $this->getKernel()->getProjectDir(), $output) === true
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
