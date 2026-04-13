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

use Stateforge\Scenario\Core\Contract\CliOutput;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Symfony\Component\Console\Command\Command;
use function array_keys;
use function count;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

final class ScenarioMakeScenarioCommand extends ScenarioMakeCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:make:scenario')
            ->setDescription('Make a scenario - should only be used for dev/test')
        ;
    }

    protected function make(Configuration $config, CliOutput $style): int
    {
        $file = $this->getBlueprint('scenario.blueprint');
        if ($this->getFilesystem()->exists($file) === false) {
            $style->error('Scenario generation failed.');
            return Command::FAILURE;
        }

        $suites = $config->getSuites();
        $options = array_keys($suites);
        $suite = $suites[$options[0]];
        if (count($suites) > 1) {
            $suite = $suites[
                $style->choice('Please select the suite where you want to make a scenario.', $options)
            ];
        }

        $name = $this->askClassname('Please insert a class name for the new scenario', $style);
        if ($name === null) {
            $style->error('Scenario generation failed.');
            return Command::FAILURE;
        }

        $scenario = $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR . $suite->directory . DIRECTORY_SEPARATOR . ucfirst($name) . '.php';
        if ($this->getFilesystem()->exists($scenario) === true) {
            $style->error('Scenario already exists.');
            return Command::FAILURE;
        }

        $generated = $this->generateFile(
            $name,
            $file,
            $scenario,
            $suite->directory,
        );

        if ($generated === false) {
            $style->error('Scenario generation failed.');
            return Command::FAILURE;
        }

        $style->success('Scenario "' . $scenario . '" generated, please modify to your needs.');
        return Command::SUCCESS;
    }
}
