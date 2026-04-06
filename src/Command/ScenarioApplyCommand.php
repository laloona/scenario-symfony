<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Command;

use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Exception\RegistryException;
use Stateforge\Scenario\Core\Runtime\Metadata\ExecutionType;
use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use Stateforge\Scenario\Symfony\Console\Output;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessRunnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function is_array;
use function is_string;
use function sprintf;
use const PHP_BINARY;

final class ScenarioApplyCommand extends ScenarioCommand
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
            ->setName('scenario:apply')
            ->setDescription('Apply a given scenario, use --up or --down to choose how the scenario should be applied - should only be used for dev/test')
            ->addArgument('scenario', InputArgument::OPTIONAL, 'Scenario name')
            ->addOption('parameter', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Scenario parameters in name=value format (repeatable)')
            ->addOption('up', null, InputOption::VALUE_NONE, 'Apply up method (default)')
            ->addOption('down', null, InputOption::VALUE_NONE, 'Apply down method')
            ->addOption('audit', null, InputOption::VALUE_NONE, 'Print out the audits')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new Output(new SymfonyStyle($input, $output));

        if ($input->getOption('up') === true
            && $input->getOption('down') === true) {
            $style->error('You can just use either up or down scenarios.');
            return Command::FAILURE;
        }

        (new Application())->prepare();
        $scenarioDefinitions = ScenarioRegistry::getInstance()->all();
        if (count($scenarioDefinitions) === 0) {
            $style->error('No scenarios were found, please create one.');
            return Command::FAILURE;
        }

        $directExecution = false;
        $scenario = $input->getArgument('scenario');
        $executionType = $input->getOption('down') === true ? ExecutionType::Down : ExecutionType::Up;
        if (is_string($scenario) === true) {
            try {
                $scenario = ScenarioRegistry::getInstance()->resolve($scenario)->class;
                $directExecution = true;
            } catch (RegistryException $exception) {
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

        /** @var list<string> $parameters */
        $parameters = [];
        if (is_string($scenario) === true) {
            if ($directExecution === true) {
                $optionParameters = $input->getOption('parameter');
                $parameters = is_array($optionParameters)
                    ? array_values(array_filter($optionParameters, is_string(...)))
                    : [];
            } else {
                $definition = $scenarioDefinitions[$scenario];
                if (count($definition->parameters) > 0) {
                    foreach ($definition->parameters as $parameter) {
                        $ask = sprintf(
                            'Please insert value for %s parameter "%s"%s%s',
                            $parameter->type->value,
                            $parameter->name,
                            $parameter->description === null ? '' : ' (' . $parameter->description . ')',
                            $parameter->required === true ? ' (required)' : '',
                        );
                        $validator = $parameter->required === true
                            ? fn ($input) => $parameter->type->valid($input) ? $input : false
                            : fn ($input) => $input === null || $parameter->type->valid($input) ? $input : false;
                        $default = $parameter->asString($parameter->default);
                        $answer = $style->ask($ask, $default, $validator);

                        if ($parameter->repeatable === true) {
                            if ($answer !== null) {
                                $parameters[] = $parameter->name . '=' . $answer;
                            }

                            while ($style->confirm('Do you want to continue?', false) === true) {
                                $answer = $style->ask($ask, $default, $validator);
                                if ($answer !== null) {
                                    $parameters[] = $parameter->name . '=' . $answer;
                                    continue;
                                }
                                break;
                            }

                            continue;
                        }

                        if ($answer !== null) {
                            $parameters[] = $parameter->name . '=' . $answer;
                        }
                    }
                }
            }

            $parameters = array_map(
                fn (string $param) => '--parameter=' . $param,
                $parameters,
            );
        }

        if ($input->getOption('audit') === true) {
            $parameters[] = '--audit';
        }

        /** @var class-string $scenario */
        $applied = $this->applyScenario($output, $scenario, $executionType, $parameters);

        if ($applied === true) {
            $style->success('Scenario "' . $scenario . '::' . $executionType->value . '" was applied successfully.');
            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }

    /**
     * @param class-string $className
     * @param list<string> $parameters
     */
    private function applyScenario(
        OutputInterface $output,
        string $className,
        ExecutionType $executionType,
        array $parameters,
    ): bool {
        return $this->processRunner->run(
            [
                PHP_BINARY,
                $this->getCliPath(),
                'apply',
                $className,
                '--' . $executionType->value,
                ...$parameters,
                '--force',
                '--quiet',
            ],
            $this->getKernel()->getProjectDir(),
            null,
            $output,
        );
    }
}
