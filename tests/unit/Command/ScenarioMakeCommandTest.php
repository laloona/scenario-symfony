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
use function str_ends_with;
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
        $scenarioExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use (&$scenarioExists): bool {
                return match (true) {
                    str_ends_with($path, 'scenario.blueprint') => true,
                    str_ends_with($path, 'DemoScenario.php') => $scenarioExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), 'scenario/main/DemoScenario.php');
                }),
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

        self::assertSame(Command::SUCCESS, $tester->execute(['type' => 'scenario'], ['interactive' => true]));
        self::assertStringContainsString('generated', $tester->getDisplay());
    }

    public function testExecuteFailsWhenScenarioAlreadyExists(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(2))
            ->method('exists')
            ->willReturnCallback(function (string $path): bool {
                return match (true) {
                    str_ends_with($path, 'scenario.blueprint') => true,
                    str_ends_with($path, 'ExistingScenario.php') => true,
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

        self::assertSame(Command::FAILURE, $tester->execute(['type' => 'scenario'], ['interactive' => true]));
        self::assertStringContainsString('Scenario already exists.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenBlueprintDoesNotExist(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::once())
            ->method('exists')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), '/vendor/stateforge/scenario-symfony/blueprint/scenario.blueprint');
                }),
            )
            ->willReturn(false);
        $filesystem->expects(self::never())
            ->method('dumpFile');

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));

        self::assertSame(Command::FAILURE, $tester->execute(['type' => 'scenario'], ['interactive' => false]));
        self::assertStringContainsString('Scenario generation failed.', $tester->getDisplay());
    }

    public function testExecuteGeneratesScenarioInSelectedSuite(): void
    {
        $this->setScenarioConfiguration($this->createConfiguration([
            'main' => new SuiteValue('main', 'scenario/main'),
            'admin' => new SuiteValue('admin', 'scenario/admin/user'),
        ]));

        $scenarioExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use (&$scenarioExists): bool {
                return match (true) {
                    str_ends_with($path, 'scenario.blueprint') => true,
                    str_ends_with($path, 'BackofficeScenario.php') => $scenarioExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), 'scenario/admin/user/BackofficeScenario.php');
                }),
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

        self::assertSame(Command::SUCCESS, $tester->execute(['type' => 'scenario'], ['interactive' => true]));
        self::assertStringContainsString('generated', $tester->getDisplay());
    }

    public function testExecuteRepeatsQuestionUntilScenarioNameIsValid(): void
    {
        $scenarioExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use (&$scenarioExists): bool {
                return match (true) {
                    str_ends_with($path, 'scenario.blueprint') => true,
                    str_ends_with($path, 'ValidScenario.php') => $scenarioExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), 'scenario/main/ValidScenario.php');
                }),
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

        self::assertSame(Command::SUCCESS, $tester->execute(['type' => 'scenario'], ['interactive' => true]));
        self::assertStringContainsString(
            'ValidScenario.php',
            $this->formatOutput($tester->getDisplay()),
        );
    }

    public function testExecuteRepeatsQuestionWhenScenarioNameContainsSpacesOrInvalidCharacters(): void
    {
        $scenarioExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use (&$scenarioExists): bool {
                return match (true) {
                    str_ends_with($path, 'scenario.blueprint') => true,
                    str_ends_with($path, 'CleanScenario.php') => $scenarioExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), 'scenario/main/CleanScenario.php');
                }),
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

        self::assertSame(Command::SUCCESS, $tester->execute(['type' => 'scenario'], ['interactive' => true]));
        self::assertStringContainsString(
            'Input was invalid, please try again:',
            $this->formatOutput($tester->getDisplay()),
        );
        self::assertStringContainsString(
            'CleanScenario.php',
            $this->formatOutput($tester->getDisplay()),
        );
    }

    public function testExecuteFailsWhenGeneratedScenarioFileCannotBeVerified(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path): bool {
                return match (true) {
                    str_ends_with($path, 'scenario.blueprint') => true,
                    str_ends_with($path, 'DemoScenario.php') => false,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), 'scenario/main/DemoScenario.php');
                }),
                self::stringContains('final class DemoScenario'),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['demoScenario']);

        self::assertSame(Command::FAILURE, $tester->execute(['type' => 'scenario'], ['interactive' => true]));
        self::assertStringContainsString('Scenario generation failed.', $tester->getDisplay());
    }

    public function testExecuteGeneratesParameterTypeFileFromBlueprint(): void
    {
        file_put_contents(
            $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/parameter.blueprint',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Stateforge\Parameter\%nameSpace%;

final class %className%
{
}
PHP,
        );

        $parameterExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use (&$parameterExists): bool {
                return match (true) {
                    str_ends_with($path, 'parameter.blueprint') => true,
                    str_ends_with($path, 'DemoParameter.php') => $parameterExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), 'scenario/parameter/DemoParameter.php');
                }),
                self::callback(function (string $content) use (&$parameterExists): bool {
                    $parameterExists = true;
                    $content = $this->formatOutput($content);
                    self::assertStringContainsString('namespace Stateforge\Parameter\Scenario\Parameter;', $content);
                    self::assertStringContainsString('final class DemoParameter', $content);

                    return true;
                }),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['demoParameter']);

        self::assertSame(Command::SUCCESS, $tester->execute(['type' => 'parameter type'], ['interactive' => true]));
        self::assertStringContainsString('generated', $tester->getDisplay());
    }

    public function testExecuteFailsWhenParameterTypeAlreadyExists(): void
    {
        file_put_contents(
            $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/parameter.blueprint',
            '<?php final class %className% {}',
        );

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(2))
            ->method('exists')
            ->willReturnCallback(function (string $path): bool {
                return match (true) {
                    str_ends_with($path, 'parameter.blueprint') => true,
                    str_ends_with($path, 'ExistingParameter.php') => true,
                    default => false,
                };
            });
        $filesystem->expects(self::never())
            ->method('dumpFile');

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['existingParameter']);

        self::assertSame(Command::FAILURE, $tester->execute(['type' => 'parameter type'], ['interactive' => true]));
        self::assertStringContainsString('Parameter type already exists.', $tester->getDisplay());
    }

    public function testExecuteGeneratesScenarioFileWhenTypeIsChosenInteractively(): void
    {
        $scenarioExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use (&$scenarioExists): bool {
                return match (true) {
                    str_ends_with($path, 'scenario.blueprint') => true,
                    str_ends_with($path, 'InteractiveScenario.php') => $scenarioExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), 'scenario/main/InteractiveScenario.php');
                }),
                self::callback(function (string $content) use (&$scenarioExists): bool {
                    $scenarioExists = true;
                    self::assertStringContainsString('final class InteractiveScenario', $this->formatOutput($content));

                    return true;
                }),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['scenario', 'interactiveScenario']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('InteractiveScenario.php', $this->formatOutput($tester->getDisplay()));
    }

    public function testExecuteGeneratesParameterTypeWhenTypeIsChosenInteractively(): void
    {
        file_put_contents(
            $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/parameter.blueprint',
            '<?php final class %className% {}',
        );

        $parameterExists = false;

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path) use (&$parameterExists): bool {
                return match (true) {
                    str_ends_with($path, 'parameter.blueprint') => true,
                    str_ends_with($path, 'InteractiveParameter.php') => $parameterExists,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), 'scenario/parameter/InteractiveParameter.php');
                }),
                self::callback(function (string $content) use (&$parameterExists): bool {
                    $parameterExists = true;
                    self::assertStringContainsString('final class InteractiveParameter', $content);

                    return true;
                }),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['parameter type', 'interactiveParameter']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('InteractiveParameter.php', $this->formatOutput($tester->getDisplay()));
    }

    public function testExecuteFailsWhenParameterTypeBlueprintDoesNotExist(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::once())
            ->method('exists')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), '/vendor/stateforge/scenario-symfony/blueprint/parameter.blueprint');
                }),
            )
            ->willReturn(false);
        $filesystem->expects(self::never())
            ->method('dumpFile');

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));

        self::assertSame(Command::FAILURE, $tester->execute(['type' => 'parameter type'], ['interactive' => false]));
        self::assertStringContainsString('Parameter type generation failed.', $tester->getDisplay());
    }

    public function testExecuteFailsWhenGeneratedParameterTypeFileCannotBeVerified(): void
    {
        file_put_contents(
            $this->projectDir . '/vendor/stateforge/scenario-symfony/blueprint/parameter.blueprint',
            '<?php final class %className% {}',
        );

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(3))
            ->method('exists')
            ->willReturnCallback(function (string $path): bool {
                return match (true) {
                    str_ends_with($path, 'parameter.blueprint') => true,
                    str_ends_with($path, 'BrokenParameter.php') => false,
                    default => false,
                };
            });
        $filesystem->expects(self::once())
            ->method('dumpFile')
            ->with(
                self::callback(function (string $path): bool {
                    return str_ends_with($this->normalizePath($path), 'scenario/parameter/BrokenParameter.php');
                }),
                self::stringContains('final class BrokenParameter'),
            );

        $tester = new CommandTester(new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            $filesystem,
        ));
        $tester->setInputs(['brokenParameter']);

        self::assertSame(Command::FAILURE, $tester->execute(['type' => 'parameter type'], ['interactive' => true]));
        self::assertStringContainsString('Parameter type generation failed.', $tester->getDisplay());
    }

    /**
     * @param array<string, SuiteValue> $suites
     */
    private function createConfiguration(array $suites): Configuration
    {
        $configuration = self::createStub(Configuration::class);
        $configuration->method('getSuites')
            ->willReturn($suites);
        $configuration->method('getParameterDirectory')
            ->willReturn('scenario/parameter');

        return $configuration;
    }
}
