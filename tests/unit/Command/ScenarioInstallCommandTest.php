<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\PHPUnit\Configuration\ConfiguredInterface;
use Stateforge\Scenario\Core\PHPUnit\Extension;
use Stateforge\Scenario\Symfony\Command\ScenarioInstallCommand;
use Stateforge\Scenario\Symfony\Console\Output;
use Stateforge\Scenario\Symfony\Runtime\ProcessRunnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use function file_get_contents;
use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use const PHP_BINARY;

#[CoversClass(ScenarioInstallCommand::class)]
#[UsesClass(Output::class)]
#[Group('command')]
#[Medium]
final class ScenarioInstallCommandTest extends TestCase
{
    use ScenarioCommand;

    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/scenario-' . uniqid('', true);
        $this->createProject();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->projectDir);
    }

    public function testCommandIsConfigured(): void
    {
        $command = new ScenarioInstallCommand(
            self::createStub(ProcessRunnerInterface::class),
            self::createStub(ConfiguredInterface::class),
            $this->getKernel($this->projectDir),
            new Filesystem(),
        );

        self::assertSame('scenario:install', $command->getName());
        self::assertSame('Install the scenario bundle  (dev/test only)', $command->getDescription());
    }

    public function testExecuteInstallsBlueprintFilesAndConfiguresPhpUnit(): void
    {
        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects(self::once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    $this->projectDir . '/vendor/bin/scenario',
                    'install',
                    '--force',
                    '--quiet',
                ],
                $this->projectDir,
                self::anything(),
            )
            ->willReturn(true);

        $configured = $this->createMock(ConfiguredInterface::class);
        $configured->expects(self::exactly(2))
            ->method('isConfigured')
            ->willReturnOnConsecutiveCalls(false, true);

        $tester = new CommandTester(new ScenarioInstallCommand(
            $processRunner,
            $configured,
            $this->getKernel($this->projectDir),
            new Filesystem(),
        ));
        $tester->setInputs(['yes', 'yes']);

        $exitCode = $tester->execute([], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertFileExists($this->projectDir . '/scenario/bootstrap.php');
        self::assertDirectoryExists($this->projectDir . '/scenario/main');
        self::assertFileExists($this->projectDir . '/scenario.dist.xml');
        self::assertFileExists($this->projectDir . '/config/packages/scenario.yaml');
    }

    public function testIsEnabledReturnsFalseWhenScenarioIsAlreadyInstalled(): void
    {
        file_put_contents($this->projectDir . '/config/packages/scenario.yaml', "scenario:\n");

        self::assertFalse(
            new ScenarioInstallCommand(
                self::createStub(ProcessRunnerInterface::class),
                self::createStub(ConfiguredInterface::class),
                $this->getKernel($this->projectDir),
                new Filesystem(),
            )->isEnabled(),
        );
    }

    public function testExecuteAbortsWhenUserDeclinesInstallation(): void
    {
        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects(self::never())
            ->method('run');

        $configured = $this->createMock(ConfiguredInterface::class);
        $configured->expects(self::never())
            ->method('isConfigured');

        $tester = new CommandTester(new ScenarioInstallCommand(
            $processRunner,
            $configured,
            $this->getKernel($this->projectDir),
            new Filesystem(),
        ));
        $tester->setInputs(['no']);

        $exitCode = $tester->execute([], ['interactive' => true]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertFileDoesNotExist($this->projectDir . '/config/packages/scenario.yaml');
    }

    public function testExecuteInstallsFilesWithoutTouchingPhpUnitWhenDeclined(): void
    {
        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects(self::never())
            ->method('run');

        $configured = $this->createMock(ConfiguredInterface::class);
        $configured->expects(self::once())
            ->method('isConfigured')
            ->willReturn(false);

        $tester = new CommandTester(new ScenarioInstallCommand(
            $processRunner,
            $configured,
            $this->getKernel($this->projectDir),
            new Filesystem(),
        ));
        $tester->setInputs(['yes', 'no']);

        $exitCode = $tester->execute([], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertFileExists($this->projectDir . '/scenario/bootstrap.php');
        self::assertStringNotContainsString(
            Extension::class,
            (string) file_get_contents($this->projectDir . '/phpunit.xml'),
        );
    }

    public function testExecuteFailsWhenScenarioYamlBlueprintIsMissing(): void
    {
        unlink($this->projectDir . '/vendor/scenario/symfony/blueprint/yaml.blueprint');

        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects(self::never())
            ->method('run');

        $configured = $this->createMock(ConfiguredInterface::class);
        $configured->expects(self::never())
            ->method('isConfigured')
            ->willReturn(false);

        $tester = new CommandTester(new ScenarioInstallCommand(
            $processRunner,
            $configured,
            $this->getKernel($this->projectDir),
            new Filesystem(),
        ));
        $tester->setInputs(['yes', 'no']);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Scenario installation failed.', $tester->getDisplay());
    }

    public function testExecuteShowsErrorWhenPhpUnitConfigurationStaysUnconfigured(): void
    {
        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects(self::once())
            ->method('run')
            ->willReturn(true);

        $configured = $this->createMock(ConfiguredInterface::class);
        $configured->expects(self::exactly(2))
            ->method('isConfigured')
            ->willReturn(false);

        $tester = new CommandTester(new ScenarioInstallCommand(
            $processRunner,
            $configured,
            $this->getKernel($this->projectDir),
            new Filesystem(),
        ));
        $tester->setInputs(['yes', 'yes']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Configuring PHPUnit failed.', $tester->getDisplay());
        self::assertStringContainsString('Scenario was successfully installed.', $tester->getDisplay());
    }

    public function testExecuteOverwritesExistingBootstrapBlueprintTarget(): void
    {
        (new Filesystem())->mkdir($this->projectDir . '/scenario');
        file_put_contents($this->projectDir . '/scenario/bootstrap.php', 'old bootstrap');

        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects(self::never())
            ->method('run');

        $configured = $this->createMock(ConfiguredInterface::class);
        $configured->expects(self::once())
            ->method('isConfigured')
            ->willReturn(true);

        $tester = new CommandTester(new ScenarioInstallCommand(
            $processRunner,
            $configured,
            $this->getKernel($this->projectDir),
            new Filesystem(),
        ));
        $tester->setInputs(['yes']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertNotSame('old bootstrap', (string) file_get_contents($this->projectDir . '/scenario/bootstrap.php'));
    }

    private function createProject(): void
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir([
            $this->projectDir . '/vendor/scenario/symfony/blueprint',
            $this->projectDir . '/config/packages',
        ]);

        file_put_contents($this->projectDir . '/vendor/scenario/symfony/blueprint/bootstrap.blueprint', '<?php return true;');
        file_put_contents($this->projectDir . '/vendor/scenario/symfony/blueprint/config.blueprint', '<scenario />');
        file_put_contents($this->projectDir . '/vendor/scenario/symfony/blueprint/yaml.blueprint', "scenario:\n  enabled: true\n");
        file_put_contents($this->projectDir . '/phpunit.xml', '<?xml version="1.0"?><phpunit></phpunit>');
    }
}
