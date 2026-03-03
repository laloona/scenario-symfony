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

use Scenario\Core\Application;
use Scenario\Core\Runtime\Exception\RegistryException;
use Scenario\Core\Runtime\ScenarioRegistry;
use Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
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
        new Application()->prepare();
        $style = new Output(new SymfonyStyle($input, $output));

        if ($input->getOption('up') === true
            && $input->getOption('down') === true) {
            $style->error('You can just use either up or down scenarios.');

            return Command::FAILURE;
        }

        $scenarioDefinitions = ScenarioRegistry::getInstance()->all();
        if (count($scenarioDefinitions) === 0) {
            $style->error('No scenarios were found, please create one.');

            return Command::FAILURE;
        }

        $scenario = $input->getArgument('scenario');
        if ($scenario === null
            && $input->isInteractive() === false) {
            $style->error('No scenario was given to apply.');
            return Command::FAILURE;
        }

        if ($scenario !== null
            && is_string($scenario) === false) {
            $scenario = null;
        }

        if ($scenario !== null) {
            try {
                ScenarioRegistry::getInstance()->resolve($scenario);
            } catch (RegistryException $exception) {
                $style->error(sprintf('Given scenario [%s] is not registered.', $scenario));
                $scenario = null;

                if ($input->isInteractive() === false) {
                    return Command::FAILURE;
                }
            }
        }

        if ($scenario === null) {
            $scenarios = [];
            foreach ($scenarioDefinitions as $scenarioDefinition) {
                $scenarios[$scenarioDefinition->class . ' (' . $scenarioDefinition->suite . ')'] = $scenarioDefinition;
            }

            $options = array_values(array_unique(array_keys($scenarios)));
            $scenario = $scenarios[$style->choice('Which scenario would you like to apply?', $options)]->class;
        }

        $executionType = $input->getOption('down') === true ? 'down' : 'up';
        $process = new Process([
            PHP_BINARY,
            $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario',
            'execute',
            $scenario,
            '--' . $executionType,
            '--force',
            '--quiet',
        ], $this->getKernel()->getProjectDir());

        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful() === true) {
            $style->success('Scenario "' . $scenario . '::' . $executionType . '" was applied successfully.');
            return Command::SUCCESS;
        }

        $error = $process->getErrorOutput();
        $style->error($error !== '' ? $error : $process->getOutput());
        return Command::FAILURE;
    }
}
