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
use function ucfirst;
use const DIRECTORY_SEPARATOR;

final class ScenarioMakeParameterCommand extends ScenarioMakeCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:make:parameter')
            ->setDescription('Make a parameter type - should only be used for dev/test')
        ;
    }

    protected function make(Configuration $config, CliOutput $style): int
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
}
