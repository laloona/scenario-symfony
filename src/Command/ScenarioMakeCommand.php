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

use Stateforge\Scenario\Core\Console\Input\Validate\ClassNameValidation;
use Stateforge\Scenario\Core\Contract\CliOutput;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Stateforge\Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function array_map;
use function explode;
use function file_get_contents;
use function implode;
use function str_replace;
use function ucfirst;

abstract class ScenarioMakeCommand extends ScenarioCommand
{
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new Application())->prepare();
        $style = new Output(new SymfonyStyle($input, $output));

        $config = Application::config();
        if ($config === null) {
            $style->error('Application configuration not found.');
            return Command::FAILURE;
        }

        return $this->make($config, $style);
    }

    final protected function askClassname(string $question, CliOutput $style): ?string
    {
        return $style->ask(
            $question,
            null,
            function (string $name): bool|string {
                if (ClassNameValidation::validate($name) === true) {
                    return $name;
                }

                return false;
            },
        );
    }

    final protected function generateFile(string $name, string $source, string $target, string $directory): bool
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

    abstract protected function make(Configuration $config, CliOutput $style): int;
}
