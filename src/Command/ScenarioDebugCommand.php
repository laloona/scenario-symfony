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
use Scenario\Core\Contract\CliOutput;
use Scenario\Core\PHPUnit\Finder\ScenarioTestFinder;
use Scenario\Core\Runtime\ScenarioDefinition;
use Scenario\Core\Runtime\ScenarioRegistry;
use Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use function array_keys;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function sprintf;

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
        $style = new Output(new SymfonyStyle($input, $output));
        (new Application())->prepare();

        $scenarioDefinitions = ScenarioRegistry::getInstance()->all();
        $testClasses = (new ScenarioTestFinder())->all();

        $type = $this->getSelectedType($style, $scenarioDefinitions, $testClasses);
        if ($type === false) {
            $style->error('No scenarios or unit tests were found, please create one.');
            return Command::FAILURE;
        }

        return match($type) {
            'Scenario' => $this->debugScenario($scenarioDefinitions, $output, $style),
            'Unit Test' => $this->debugTest($testClasses, $output, $style),
        };
    }

    /**
     * @param array<class-string|string, ScenarioDefinition> $scenarioDefinitions
     * @param array<class-string, list<non-empty-string>> $testClasses
     * @return 'Scenario'|'Unit Test'|false
     */
    private function getSelectedType(CliOutput $style, array $scenarioDefinitions, array $testClasses): string|false
    {
        if (count($scenarioDefinitions) === 0
            && count($testClasses) === 0) {
            return false;
        }

        if (count($scenarioDefinitions) === 0) {
            return 'Unit Test';
        }

        if (count($testClasses) === 0) {
            return 'Scenario';
        }

        /** @var 'Scenario'|'Unit Test' $selected */
        $selected = $style->choice('Which kind of class would you like to debug?', ['Scenario', 'Unit Test']);
        return $selected;
    }

    /**
     * @param array<class-string|string, ScenarioDefinition> $scenarioDefinitions
     */
    private function debugScenario(array $scenarioDefinitions, OutputInterface $output, CliOutput $style): int
    {
        $scenarios = [];
        foreach ($scenarioDefinitions as $scenarioDefinition) {
            $scenarios[$scenarioDefinition->class . ' (' . $scenarioDefinition->suite . ')'] = $scenarioDefinition;
        }

        /** @var list<non-falsy-string> $options */
        $options = array_values(array_unique(array_keys($scenarios)));
        $choosen = $style->choice('Which scenario would you like to debug?', $options);

        /** @var class-string $scenarioClass */
        $scenarioClass = $scenarios[$choosen]->class;
        $this->runDebugClass($output, $scenarioClass, null);

        return Command::SUCCESS;
    }

    /**
     * @param array<class-string, list<non-empty-string>> $classesMethods
     */
    private function debugTest(array $classesMethods, OutputInterface $output, CliOutput $style): int
    {
        /** @var list<class-string> $testClasses */
        $testClasses = array_keys($classesMethods);

        if (count($testClasses) === 1) {
            $testClass = array_shift($testClasses);
        } else {
            /** @var class-string $testClass */
            $testClass = $style->choice('Which class would you like to debug?', $testClasses);
        }

        /** @var list<non-empty-string> $methods */
        $methods = $classesMethods[$testClass];

        if (count($methods) === 0) {
            $this->runDebugClass($output, $testClass, null);
            return Command::SUCCESS;
        }

        if (count($methods) === 1) {
            $method = array_shift($methods);
        } else {
            $method = $style->choice(sprintf('Which method would you like to debug from %s?', $testClass), $methods);
        }

        $this->runDebugClass($output, $testClass, $method);
        return Command::SUCCESS;
    }

    /**
     * @param class-string $testClass
     */
    private function runDebugClass(OutputInterface $output, string $testClass, ?string $method): bool
    {
        $arguments = [
            $testClass,
        ];
        if ($method !== null) {
            $arguments[] = $method;
        }

        $process = new Process([
            PHP_BINARY,
            $this->getCliPath(),
            'debug',
            ...$arguments,
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
