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
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Stateforge\Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_keys;
use function array_map;
use function count;
use function explode;
use function file_get_contents;
use function implode;
use function in_array;
use function preg_match;
use function str_replace;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

final class ScenarioMakeCommand extends ScenarioCommand
{
    private const PATTERN_CLASSNAME = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/';

    protected function configure(): void
    {
        $this
            ->setName('scenario:make')
            ->setDescription('Make a scenario - should only be used for dev/test')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type to make')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new Application())->prepare();
        $style = new Output(new SymfonyStyle($input, $output));

        $config = Application::config();
        if ($config === null) {
            $style->error('Application configuration not found.');
            return Command::FAILURE;
        }

        $type = $input->getArgument('type') ?? '';
        $options = ['scenario', 'parameter type'];
        if (in_array($type, $options, true) === false) {
            $type = $style->choice('Please select the type do would like to make.', $options, 'scenario');
        }

        return match ($type) {
            'scenario' => $this->scenario($config, $style),
            'parameter type' => $this->parameter($config, $style),
            default => Command::FAILURE,
        };
    }

    private function scenario(Configuration $config, CliOutput $style): int
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

    private function parameter(Configuration $config, CliOutput $style): int
    {
        $file = $this->getBlueprint('parameter.blueprint');
        if ($this->getFilesystem()->exists($file) === false) {
            $style->error('Parameter type generation failed.');
            return Command::FAILURE;
        }

        $name = $this->askClassname('Please insert a class name for the new parameter type', $style);
        if ($name === null) {
            $style->error('Parameter type generation failed.');
            return Command::FAILURE;
        }

        $parameterType = $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR . $config->getParameterDirectory() . DIRECTORY_SEPARATOR . ucfirst($name) . '.php';
        if ($this->getFilesystem()->exists($parameterType) === true) {
            $style->error('Parameter type already exists.');
            return Command::FAILURE;
        }

        $generated = $this->generateFile(
            $name,
            $file,
            $parameterType,
            $config->getParameterDirectory(),
        );

        if ($generated === false) {
            $style->error('Parameter type generation failed.');
            return Command::FAILURE;
        }

        $style->success('Parameter type "' . $parameterType . '" generated, please modify to your needs.');
        return Command::SUCCESS;
    }

    private function askClassname(string $question, CliOutput $style): ?string
    {
        return $style->ask(
            $question,
            null,
            function (string $name): bool|string {
                if (preg_match(self::PATTERN_CLASSNAME, $name) === 1) {
                    return $name;
                }

                return false;
            },
        );
    }

    private function generateFile(string $name, string $source, string $target, string $directory): bool
    {
        // with symfony 7 it can be replaced with filesystem::readfile
        $content = file_get_contents($source);
        if ($content === false) {
            return false;
        }

        $this->getFilesystem()->dumpFile(
            $target,
            str_replace(
                [ '%nameSpace%', '%className%' ],
                [
                    implode('\\', array_map(function ($part) {
                        return ucfirst($part);
                    }, explode('/', str_replace('\\', '/', $directory)))),
                    ucfirst($name),
                ],
                $content,
            ),
        );

        if ($this->getFilesystem()->exists($target) === false) {
            return false;
        }

        return true;
    }
}
