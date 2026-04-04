<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Command;

use Stateforge\Scenario\Core\PHPUnit\Configuration\ConfiguredInterface;
use Stateforge\Scenario\Symfony\Console\Output;
use Stateforge\Scenario\Symfony\Runtime\ProcessRunnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

final class ScenarioInstallCommand extends ScenarioCommand
{
    public function __construct(
        private ProcessRunnerInterface $processRunner,
        private ConfiguredInterface $configured,
        KernelInterface $kernel,
        Filesystem $filesystem,
    ) {
        parent::__construct($kernel, $filesystem);
    }

    protected function configure(): void
    {
        $this
            ->setName('scenario:install')
            ->setDescription('Install the scenario bundle  (dev/test only)')
        ;
    }

    public function isEnabled(): bool
    {
        return $this->isAllowed() === true
            && $this->isInstalled() === false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new Output(new SymfonyStyle($input, $output));

        if ($style->confirm('Do you want to install the scenario bundle?', true) === false) {
            $style->error('Scenario installation aborted.');
            return Command::FAILURE;
        }

        $this->copyBlueprint(
            'bootstrap.blueprint',
            'scenario' . DIRECTORY_SEPARATOR . 'bootstrap.php',
        );

        $this->getFilesystem()->mkdir($this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR . 'scenario' . DIRECTORY_SEPARATOR . 'main');

        $this->copyBlueprint(
            'config.blueprint',
            'scenario.dist.xml',
        );

        $this->copyBlueprint(
            'yaml.blueprint',
            'config' . DIRECTORY_SEPARATOR . 'packages' .  DIRECTORY_SEPARATOR . 'scenario.yaml',
        );

        if ($this->isInstalled() === true) {
            if ($this->configured->isConfigured() === false
                && $style->confirm('Do you want to add configuration to PHPUnit?', true)) {
                $this->configurePHPUnit($output);
                if ($this->configured->isConfigured() === false) {
                    $style->error('Configuring PHPUnit failed.');
                }
            }

            $style->success('Scenario was successfully installed.');
            return Command::SUCCESS;
        }

        $style->error('Scenario installation failed.');
        return Command::FAILURE;
    }

    private function copyBlueprint(string $source, string $target): void
    {
        $source = $this->getBlueprint($source);
        $target = $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR . $target;

        if ($this->getFilesystem()->exists($source) === false) {
            return;
        }

        // when target exists we overwrite it
        if ($this->getFilesystem()->exists($target)) {
            $this->getFilesystem()->remove([$target]);
        }

        $this->getFilesystem()->copy($source, $target);
    }

    private function configurePHPUnit(OutputInterface $output): void
    {
        $this->processRunner->run(
            [
                PHP_BINARY,
                $this->getCliPath(),
                'install',
                '--force',
                '--quiet',
            ],
            $this->getKernel()->getProjectDir(),
            $output,
        );
    }
}
