<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Scenario\Core\PHPUnit\Extension;
use Scenario\Symfony\Command\ScenarioInstallCommand;
use Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

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

    public function testExecuteInstallsBlueprintFilesAndConfiguresPhpUnit(): void
    {
        $tester = new CommandTester(new ScenarioInstallCommand(
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
        self::assertStringContainsString(
            '<bootstrap class="' . Extension::class . '"/>',
            (string) file_get_contents($this->projectDir . '/phpunit.xml'),
        );
    }

    public function testIsEnabledReturnsFalseWhenScenarioIsAlreadyInstalled(): void
    {
        file_put_contents($this->projectDir . '/config/packages/scenario.yaml', "scenario:\n");

        self::assertFalse(
            new ScenarioInstallCommand(
                $this->getKernel($this->projectDir),
                new Filesystem(),
            )->isEnabled(),
        );
    }

    public function testExecuteAbortsWhenUserDeclinesInstallation(): void
    {
        $tester = new CommandTester(new ScenarioInstallCommand(
            $this->getKernel($this->projectDir),
            new Filesystem(),
        ));
        $tester->setInputs(['no']);

        $exitCode = $tester->execute([], ['interactive' => true]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertFileDoesNotExist($this->projectDir . '/config/packages/scenario.yaml');
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
