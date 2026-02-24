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
use Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ScenarioMakeCommand extends ScenarioCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:make')
            ->setDescription('Make a scenario - should only be used for dev/test')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        new Application()->prepare();
        $style = new Output(new SymfonyStyle($input, $output));

        $file = $this->getBlueprint('scenario.blueprint');
        if ($this->getFilesystem()->exists($file) === false) {
            $style->error('Scenario generation failed.');
            return Command::FAILURE;
        }

        $config = Application::config();
        if ($config === null) {
            $style->error('Application configuration not found.');
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

        $name = $style->ask(
            'Please insert a class name for the new scenario',
            null,
            function (string $name): bool|string {
                if (preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name) === 1) {
                    return $name;
                }

                return false;
            },
        );

        $scenario = $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR . $suite->directory . DIRECTORY_SEPARATOR . ucfirst($name) . '.php';
        if ($this->getFilesystem()->exists($scenario) === true) {
            $style->error('Scenario already exists.');
            return Command::FAILURE;
        }

        $this->getFilesystem()->dumpFile(
            $scenario,
            str_replace(
                [ '%nameSpace%', '%className%' ],
                [ implode('\\', array_map(function ($part) {
                    return ucfirst($part);
                }, explode(DIRECTORY_SEPARATOR, $suite->directory))), ucfirst($name) ],
                $this->getFilesystem()->readFile($file),
            ),
        );

        if ($this->getFilesystem()->exists($scenario) === false) {
            $style->error('Scenario generation failed.');
            return Command::FAILURE;
        }

        $style->success('Scenario "' . $scenario . '" generated, please modify to your needs.');
        return Command::SUCCESS;
    }
}
