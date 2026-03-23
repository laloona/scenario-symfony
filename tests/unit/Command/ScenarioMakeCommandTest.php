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
}
