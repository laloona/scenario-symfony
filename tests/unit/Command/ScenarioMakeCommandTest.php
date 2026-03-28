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
use ReflectionMethod;
use Scenario\Core\Runtime\Application\Configuration\Configuration;
use Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use Scenario\Symfony\Command\ScenarioMakeCommand;
use Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(ScenarioMakeCommand::class)]
#[UsesClass(Output::class)]
#[Group('command')]
#[Medium]
final class ScenarioMakeCommandTest extends TestCase
{
    use ScenarioCommand;

    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/scenario-make-' . uniqid('', true);

        $filesystem = new Filesystem();
        $filesystem->mkdir([
            $this->projectDir . '/config/packages',
            $this->projectDir . '/vendor/scenario/symfony/blueprint',
            $this->projectDir . '/scenario/main',
        ]);

        file_put_contents($this->projectDir . '/config/packages/scenario.yaml', "scenario:\n");
        file_put_contents(
            $this->projectDir . '/vendor/scenario/symfony/blueprint/scenario.blueprint',
            <<<'PHP'
<?php declare(strict_types=1);

namespace %nameSpace%;

final class %className%
{
}
PHP,
        );

        $configuration = self::createStub(Configuration::class);
        $configuration->method('getSuites')
            ->willReturn([
                'main' => new SuiteValue('main', 'scenario/main'),
            ]);

        $this->setScenarioConfiguration($configuration);
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
            new Filesystem(),
        );

        self::assertSame('scenario:make', $command->getName());
        self::assertSame('Make a scenario - should only be used for dev/test', $command->getDescription());
    }

    public function testExecuteGeneratesScenarioFileFromBlueprint(): void
    {
        $command = new ScenarioMakeCommand(
            $this->getKernel($this->projectDir),
            new Filesystem(),
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['demoScenario']);

        $scenarioFile = $this->projectDir . '/scenario/main/DemoScenario.php';

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertFileExists($scenarioFile);
        self::assertStringContainsString('namespace Scenario\\Main;', (string) file_get_contents($scenarioFile));
        self::assertStringContainsString('final class DemoScenario', (string) file_get_contents($scenarioFile));
    }

    public function testExecuteFailsWhenScenarioAlreadyExists(): void
    {
        file_put_contents($this->projectDir . '/scenario/main/ExistingScenario.php', '<?php');

        $tester = new CommandTester(
            new ScenarioMakeCommand(
                $this->getKernel($this->projectDir),
                new Filesystem(),
            ),
        );
        $tester->setInputs(['existingScenario']);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => true]));
    }

    public function testExecuteFailsWhenBlueprintDoesNotExist(): void
    {
        unlink($this->projectDir . '/vendor/scenario/symfony/blueprint/scenario.blueprint');

        self::assertSame(
            Command::FAILURE,
            new CommandTester(new ScenarioMakeCommand(
                $this->getKernel($this->projectDir),
                new Filesystem(),
            ))->execute([], ['interactive' => false]),
        );
    }

    public function testExecuteGeneratesScenarioInSelectedSuite(): void
    {
        (new Filesystem())->mkdir($this->projectDir . '/scenario/admin/user');

        $configuration = self::createStub(Configuration::class);
        $configuration->method('getSuites')
            ->willReturn([
                'main' => new SuiteValue('main', 'scenario/main'),
                'admin' => new SuiteValue('admin', 'scenario/admin/user'),
            ]);
        $this->setScenarioConfiguration($configuration);

        $tester = new CommandTester(
            new ScenarioMakeCommand(
                $this->getKernel($this->projectDir),
                new Filesystem(),
            ),
        );
        $tester->setInputs(['admin', 'backofficeScenario']);

        $scenarioFile = $this->projectDir . '/scenario/admin/user/BackofficeScenario.php';

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertFileExists($scenarioFile);
        self::assertStringContainsString('namespace Scenario\\Admin\\User;', (string) file_get_contents($scenarioFile));
        self::assertStringContainsString('final class BackofficeScenario', (string) file_get_contents($scenarioFile));
    }

    public function testExecuteRepeatsQuestionUntilScenarioNameIsValid(): void
    {
        $tester = new CommandTester(
            new ScenarioMakeCommand(
                $this->getKernel($this->projectDir),
                new Filesystem(),
            ),
        );
        $tester->setInputs(['123invalid', 'validScenario']);

        $scenarioFile = $this->projectDir . '/scenario/main/ValidScenario.php';

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
        self::assertFileExists($scenarioFile);
        self::assertStringContainsString('final class ValidScenario', (string) file_get_contents($scenarioFile));
    }

    public function testExecuteFailsWhenGeneratedScenarioFileDoesNotExistAfterDump(): void
    {
        $filesystem = new class extends Filesystem {
            public function dumpFile(string $filename, $content): void
            {
            }
        };

        $tester = new CommandTester(
            new ScenarioMakeCommand(
                $this->getKernel($this->projectDir),
                $filesystem,
            ),
        );
        $tester->setInputs(['demoScenario']);

        self::assertSame(Command::FAILURE, $tester->execute([], ['interactive' => true]));
        self::assertStringContainsString('Scenario generation failed.', $tester->getDisplay());
    }
}
