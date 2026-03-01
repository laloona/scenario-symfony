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

use DOMDocument;
use DOMElement;
use DOMXPath;
use Scenario\Core\PHPUnit\Extension;
use Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ScenarioInstallCommand extends ScenarioCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:install')
            ->setDescription('Install the scenario bundle.')
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

        if ($style->confirm('Do you want to install the scenario bundle?', true)) {
            $this->copyBlueprint(
                'bootstrap.blueprint',
                'scenario' . DIRECTORY_SEPARATOR . 'boostrap.php',
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

            if ($style->confirm('Do you want to add configuration to PHPUnit?', true)) {
                $this->configurePHPUnit();
            }

            if ($this->isInstalled() === true) {
                $style->success('Bundle was successfully installed.');
                return Command::SUCCESS;
            }

            $style->error('Bundle installation failed.');
            return Command::FAILURE;
        }

        $style->error('Bundle installation aborted.');
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

    private function configurePHPUnit(): void
    {
        $files = [
            $this->getKernel()->getProjectDir() . '/phpunit.dist.xml',
            $this->getKernel()->getProjectDir() . '/phpunit.xml',
        ];

        $phpunitFile = null;
        foreach ($files as $file) {
            if (file_exists($file)) {
                $phpunitFile = $file;
                break;
            }
        }

        if ($phpunitFile === null) {
            return;
        }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = true;
        $dom->load($phpunitFile);

        $xpath = new DOMXPath($dom);
        $extensionClass = Extension::class;

        $existing = $xpath->query("//extensions/bootstrap[@class='{$extensionClass}']");
        if ($existing === false
            || $existing->length > 0) {
            return;
        }

        $phpunitNode = $dom->getElementsByTagName('phpunit')->item(0);
        if ($phpunitNode === null) {
            return;
        }

        $extensions = $xpath->query('//extensions');
        if ($extensions === false
            || $extensions->length === 0) {
            $extensionsNode = $dom->createElement('extensions');
            $phpunitNode->appendChild($extensionsNode);
        } else {
            $extensionsNode = $extensions->item(0);
            if (!$extensionsNode instanceof DOMElement) {
                return;
            }
        }

        $bootstrapNode = $dom->createElement('bootstrap');
        $bootstrapNode->setAttribute('class', $extensionClass);
        $extensionsNode->appendChild($bootstrapNode);

        $dom->save($phpunitFile);
    }
}
