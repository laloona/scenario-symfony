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
use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Stateforge\Scenario\Core\Runtime\ScenarioDefinition;
use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use Stateforge\Scenario\Symfony\Command\ScenarioDebugCommand;
use Stateforge\Scenario\Symfony\Console\Output;
use Stateforge\Scenario\Symfony\Runtime\ProcessRunnerInterface;
use Stateforge\Scenario\Symfony\Tests\Files\ValidScenario;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

#[CoversClass(ScenarioDebugCommand::class)]
#[UsesClass(Output::class)]
#[Group('command')]
#[Medium]
final class ScenarioDebugCommandTest extends TestCase
{
    use ScenarioCommand;

    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/scenario-' . uniqid('', true);
        (new Filesystem())->mkdir($this->projectDir);

        $this->setScenarioConfiguration($this->createMock(Configuration::class));
        $this->setApplicationRootDir($this->projectDir);
        ScenarioRegistry::getInstance()->clear();
    }

    protected function tearDown(): void
    {
        ScenarioRegistry::getInstance()->clear();
        $this->setScenarioConfiguration(null);
        $this->setApplicationRootDir(null);
        (new Filesystem())->remove($this->projectDir);
    }

    public function testCommandIsConfigured(): void
    {
        $command = new ScenarioDebugCommand(
            $this->createMock(ProcessRunnerInterface::class),
            $this->getKernel($this->projectDir),
            $this->getFilesystem(),
        );

        self::assertSame('scenario:debug', $command->getName());
        self::assertSame('Debug a given scenario or Unit test - should only be used for dev/test', $command->getDescription());
    }

    public function testExecuteFailsWhenNoScenariosOrUnitTestsAreFound(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->never())
            ->method('run');

        $output = new BufferedOutput();

        self::assertSame(
            Command::FAILURE,
            (new ScenarioDebugCommand(
                $runner,
                $this->getKernel($this->projectDir),
                $this->getFilesystem(),
            ))->run(new ArrayInput([]), $output),
        );
        self::assertStringContainsString('No scenarios or unit tests were found, please create one.', $output->fetch());
    }

    public function testExecuteWithSuccesWhenOnlyScenariosAndNoTestsAreFound(): void
    {
        ScenarioRegistry::getInstance()->register(
            new ScenarioDefinition(
                'main',
                ValidScenario::class,
                new AsScenario('valid'),
                [],
            ),
        );

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with([
                PHP_BINARY,
                $this->projectDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario', 'debug',
                ValidScenario::class,
                '--force',
                '--quiet',
            ]);

        $tester = new CommandTester(
            new ScenarioDebugCommand(
                $runner,
                $this->getKernel($this->projectDir),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['0']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
    }

    public function testExecuteWithSuccesWhenOnlyMultipleTestsAndNoScenariosAreFound(): void
    {
        file_put_contents($this->projectDir . '/phpunit.xml', <<<XML
<?xml version="1.0"?>
<phpunit>
  <testsuites>
    <testsuite name="unit">
      <directory>tests/</directory>
    </testsuite>
  </testsuites>
</phpunit>
XML);

        (new Filesystem())->mkdir($this->projectDir . '/tests');
        file_put_contents(
            $this->projectDir . '/tests/MyNewTest1.php',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

final class MyNewTest1 extends TestCase
{
    #[RefreshDatabase]
    public function myTest(): void
    {}
}
PHP
        );

        file_put_contents(
            $this->projectDir . '/tests/MyNewTest2.php',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Attribute\ApplyScenario;

final class MyNewTest2 extends TestCase
{
    #[ApplyScenario('my-scenario')]
    public function myTest(): void
    {}
}
PHP
        );

        file_put_contents(
            $this->projectDir . '/tests/MyNewTest3.php',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

#[RefreshDatabase]
final class MyNewTest3 extends TestCase
{
    public function myTest(): void
    {}
}
PHP
        );

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with([
                PHP_BINARY,
                $this->projectDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario', 'debug',
                'Tests\Unit\MyNewTest1',
                'myTest',
                '--force',
                '--quiet',
            ]);

        $tester = new CommandTester(
            new ScenarioDebugCommand(
                $runner,
                $this->getKernel($this->projectDir),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['2']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
    }

    public function testExecuteWithSuccesWhenOnlyOneTestAndNoScenariosAreFound(): void
    {
        file_put_contents($this->projectDir . '/phpunit.xml', <<<XML
<?xml version="1.0"?>
<phpunit>
  <testsuites>
    <testsuite name="unit">
      <directory>tests/</directory>
    </testsuite>
  </testsuites>
</phpunit>
XML);

        (new Filesystem())->mkdir($this->projectDir . '/tests');
        file_put_contents(
            $this->projectDir . '/tests/MyTest.php',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Attribute\ApplyScenario;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

final class MyTest extends TestCase
{
    #[RefreshDatabase]
    public function myTest(): void
    {}
    
    #[ApplyScenario]
    public function myOtherTest(): void
    {}
}
PHP,
        );

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with([
                PHP_BINARY,
                $this->projectDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario', 'debug',
                'Tests\Unit\MyTest',
                'myOtherTest',
                '--force',
                '--quiet',
            ]);

        $tester = new CommandTester(
            new ScenarioDebugCommand(
                $runner,
                $this->getKernel($this->projectDir),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['1']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
    }

    public function testExecuteWithSuccesWhenOneTestAndScenariosAreBothFoundSelectingTest(): void
    {
        ScenarioRegistry::getInstance()->register(
            new ScenarioDefinition(
                'main',
                ValidScenario::class,
                new AsScenario('valid'),
                [],
            ),
        );

        file_put_contents($this->projectDir . '/phpunit.xml', <<<XML
<?xml version="1.0"?>
<phpunit>
  <testsuites>
    <testsuite name="unit">
      <directory>tests/</directory>
    </testsuite>
  </testsuites>
</phpunit>
XML);

        (new Filesystem())->mkdir($this->projectDir . '/tests');
        file_put_contents(
            $this->projectDir . '/tests/MyTest1.php',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

final class MyTest1 extends TestCase
{
    #[RefreshDatabase]
    public function myTest(): void
    {}
}
PHP,
        );

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with([
                PHP_BINARY,
                $this->projectDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario', 'debug',
                'Tests\Unit\MyTest1',
                'myTest',
                '--force',
                '--quiet',
            ]);

        $tester = new CommandTester(
            new ScenarioDebugCommand(
                $runner,
                $this->getKernel($this->projectDir),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['1']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
    }

    public function testExecuteWithSuccesWhenTestsAndScenariosAreBothFoundSelectingTest(): void
    {
        ScenarioRegistry::getInstance()->register(
            new ScenarioDefinition(
                'main',
                ValidScenario::class,
                new AsScenario('valid'),
                [],
            ),
        );

        file_put_contents($this->projectDir . '/phpunit.xml', <<<XML
<?xml version="1.0"?>
<phpunit>
  <testsuites>
    <testsuite name="unit">
      <directory>tests/</directory>
    </testsuite>
  </testsuites>
</phpunit>
XML);

        (new Filesystem())->mkdir($this->projectDir . '/tests');
        file_put_contents(
            $this->projectDir . '/tests/MyTest2.php',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

final class MyTest2 extends TestCase
{
    #[RefreshDatabase]
    public function myTest(): void
    {}
}
PHP,
        );
        file_put_contents(
            $this->projectDir . '/tests/MyTest3.php',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

final class MyTest3 extends TestCase
{
    #[RefreshDatabase]
    public function myTest(): void
    {}
}
PHP,
        );

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with([
                PHP_BINARY,
                $this->projectDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario', 'debug',
                'Tests\Unit\MyTest3',
                'myTest',
                '--force',
                '--quiet',
            ]);

        $tester = new CommandTester(
            new ScenarioDebugCommand(
                $runner,
                $this->getKernel($this->projectDir),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['1', '0']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
    }

    public function testExecuteWithSuccesWhenTestsAndScenariosAreBothFoundSelectingScenario(): void
    {
        ScenarioRegistry::getInstance()->register(
            new ScenarioDefinition(
                'main',
                ValidScenario::class,
                new AsScenario('valid'),
                [],
            ),
        );

        file_put_contents($this->projectDir . '/phpunit.xml', <<<XML
<?xml version="1.0"?>
<phpunit>
  <testsuites>
    <testsuite name="unit">
      <directory>tests/</directory>
    </testsuite>
  </testsuites>
</phpunit>
XML);

        (new Filesystem())->mkdir($this->projectDir . '/tests');
        file_put_contents(
            $this->projectDir . '/tests/MyTest4.php',
            <<<'PHP'
<?php declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

final class MyTest4 extends TestCase
{
    #[RefreshDatabase]
    public function myTest(): void
    {}
}
PHP,
        );

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with([
                PHP_BINARY,
                $this->projectDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scenario', 'debug',
                ValidScenario::class,
                '--force',
                '--quiet',
            ]);

        $tester = new CommandTester(
            new ScenarioDebugCommand(
                $runner,
                $this->getKernel($this->projectDir),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['0', '0']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
    }
}
