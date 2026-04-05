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
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use Stateforge\Scenario\Symfony\Command\ScenarioMakeCommand;
use Stateforge\Scenario\Symfony\Console\Output;
use Stateforge\Scenario\Symfony\Tests\Unit\PathHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(ScenarioMakeCommand::class)]
#[UsesClass(Output::class)]
#[Group('command')]
#[Medium]
final class ScenarioMakeCommandTest extends TestCase
{
    use ScenarioCommand;
    use PathHelper;

    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/scenario-' . uniqid('', true);

        $filesystem = new Filesystem();
        $filesystem->mkdir([
            $this->projectDir . '/config/packages',
            $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint',
            $this->projectDir . '/scenario/main',
            $this->projectDir . '/scenario/admin/user',
        ]);

        file_put_contents($this->projectDir . '/config/packages/scenario.yaml', "scenario:\n");
        file_put_contents(
            $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/scenario.blueprint',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Stateforge\Suite\%nameSpace%;

final class %className%
{
}
PHP,
        );

        $this->setScenarioConfiguration($this->createConfiguration([
            'main' => new SuiteValue('main', 'scenario/main'),
        ]));
    }

    protected function tearDown(): void
    {
        $this->setScenarioConfiguration(null);
        (new Filesystem())->remove($this->projectDir);
    }

    public function testCommandIsConfigured(): void
    {
        $command = new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            self::createStub(Filesystem::class),
        );

        self::assertSame('scenario:make', $command->getName());
        self::assertSame('Make a scenario - should only be used for dev/test', $command->getDescription());
    }

    public function testExecuteGeneratesScenarioFileFromBlueprint(): void
    {
        $blueprint = $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/scenario.blueprint';
        $scenarioFile = $this->projectDir . '/scenario/main/DemoScenario.php';
        $scenarioExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($path) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                $scenarioFile,
                self::callback(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    $content = $this->formatOutput($content);
                    self::assertStringContainsString('namespace Stateforge\\Suite\\Scenario\\Main;', $content);
                    self::assertStringContainsString('final class DemoScenario', $content);

                    return true;
                }),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['demoScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('generated', $tester->getDisplay());
    }

    public function testExecuteFailsWhenScenarioAlreadyExists(): void
    {
        $blueprint = $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/scenario.blueprint';
        $scenarioFile = $this->projectDir . '/scenario/main/ExistingScenario.php';

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(2))
            ->method('exists')
            ->willReturnCallback(function (string $path) use ($blueprint, $scenarioFile): bool {
                return match ($this->normalizePath($path)) {
                    $blueprint => true,
                    $scenarioFile => true,
                    default => false,
                };
            });
        $filesystem->expects(self::never())
            ->method('dumpFile');

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['existingScenario']);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Scenario already exists.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenBlueprintDoesNotExist(): void
    {
        $blueprint = $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/scenario.blueprint';

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::once())
            ->method('exists')
            ->with($blueprint)
            ->willReturn(false);
        $filesystem->expects(self::never())
            ->method('dumpFile');

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => false]));
        self::assertStringContainsString('Scenario generation failed.', $tester->getDisplay());
    }

    public function testExecuteGeneratesScenarioInSelectedSuite(): void
    {
        $this->setScenarioConfiguration($this->createConfiguration([
            'main' => new SuiteValue('main', 'scenario/main'),
            'admin' => new SuiteValue('admin', 'scenario/admin/user'),
        ]));

        $blueprint = $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/scenario.blueprint';
        $scenarioFile = $this->projectDir . '/scenario/admin/user/BackofficeScenario.php';
        $scenarioExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($this->normalizePath($path)) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                $scenarioFile,
                self::callback(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    $content = $this->formatOutput($content);
                    self::assertStringContainsString('namespace Stateforge\\Suite\\Scenario\\Admin\\User;', $content);
                    self::assertStringContainsString('final class BackofficeScenario', $content);

                    return true;
                }),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['admin', 'backofficeScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('generated', $tester->getDisplay());
    }

    public function testExecuteRepeatsQuestionUntilScenarioNameIsValid(): void
    {
        $blueprint = $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/scenario.blueprint';
        $scenarioFile = $this->projectDir . '/scenario/main/ValidScenario.php';
        $scenarioExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($this->normalizePath($path)) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                $scenarioFile,
                self::callback(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    self::assertStringContainsString('final class ValidScenario', $this->formatOutput($content));
                    return true;
                }),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['123invalid', 'validScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString(
            'Scenario "' . $scenarioFile . '" generated',
            $this->formatOutput($tester->getDisplay()),
        );
    }

    public function testExecuteRepeatsQuestionWhenScenarioNameContainsSpacesOrInvalidCharacters(): void
    {
        $blueprint = $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/scenario.blueprint';
        $scenarioFile = $this->projectDir . '/scenario/main/CleanScenario.php';
        $scenarioExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use ($blueprint, $scenarioFile, &$scenarioExists): bool {
                return match ($this->normalizePath($path)) {
                    $blueprint => true,
                    $scenarioFile => $scenarioExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                $scenarioFile,
                self::callback(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    self::assertStringContainsString('final class CleanScenario', $this->formatOutput($content));
                    return true;
                }),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['bad name!', 'cleanScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString(
            'Input was invalid, please try again:',
            $this->formatOutput($tester->getDisplay()),
        );
        self::assertStringContainsString(
            'Scenario "' . $scenarioFile . '" generated',
            $this->formatOutput($tester->getDisplay()),
        );
    }

    public function testExecuteFailsWhenGeneratedScenarioFileCannotBeVerified(): void
    {
        $blueprint = $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/scenario.blueprint';
        $scenarioFile = $this->projectDir . '/scenario/main/DemoScenario.php';

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use ($blueprint, $scenarioFile): bool {
                return match ($this->normalizePath($path)) {
                    $blueprint => true,
                    $scenarioFile => false,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                $scenarioFile,
                self::stringContains('final class DemoScenario'),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['demoScenario']);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Scenario generation failed.', $tester->getDisplay());
    }

    /**
     * @param array<string, SuiteValue> $suites
     */
    private function createConfiguration(array $suites): Configuration
    {
        $configuration = self::createStub(Configuration::class);
        $configuration->method('getSuites')
            ->willReturn($suites);

        return $configuration;
    }
}
