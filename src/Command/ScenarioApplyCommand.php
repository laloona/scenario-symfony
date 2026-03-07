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
use Scenario\Core\Runtime\Metadata\ExecutionType;
use Scenario\Core\Runtime\ScenarioRegistry;
use Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use function array_keys;
use function count;
use function is_string;
use function sprintf;

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
        (new Application())->prepare();
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
        $executionType = $input->getOption('down') === true ? ExecutionType::Down : ExecutionType::Up;
        if (is_string($scenario) === true) {
            $scenarioClass = null;
            foreach ($scenarioDefinitions as $scenarioDefinition) {
                if ($scenarioDefinition->class === $scenario) {
                    $scenarioClass = $scenarioDefinition->class;
                    break;
                }
            }

            if ($scenarioClass === null) {
                $style->error(sprintf('Given scenario [%s] is not registered.', $scenario));
                $scenario = null;
            }
        }

        if ($scenario === null) {
            $scenarios = [];
            foreach ($scenarioDefinitions as $scenarioDefinition) {
                $scenarios[$scenarioDefinition->class . ' (' . $scenarioDefinition->suite . ')'] = $scenarioDefinition;
            }

            $options = array_keys($scenarios);
            $scenario = $scenarios[$style->choice('Which scenario would you like to apply?', $options)]->class;
        }

        /** @var class-string $scenario */
        $applied = $this->applyScenario($output, $scenario, $executionType);

        if ($applied === true) {
            $style->success('Scenario "' . $scenario . '::' . $executionType->value . '" was applied successfully.');
            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }

    /**
     * @param class-string $className
     */
    private function applyScenario(OutputInterface $output, string $className, ExecutionType $executionType): bool
    {
        $process = new Process([
            PHP_BINARY,
            $this->getCliPath(),
            'apply',
            $className,
            $executionType->value,
            '--force',
            '--quiet',
        ], $this->getKernel()->getProjectDir());

        $process->setTimeout(null);
        $process->setTty(Process::isTtySupported());
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        return $process->isSuccessful();
    }
}
