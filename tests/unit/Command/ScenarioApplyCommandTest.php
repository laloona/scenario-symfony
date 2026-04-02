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
use ReflectionClass;
use Scenario\Core\Attribute\AsScenario;
use Scenario\Core\Attribute\Parameter;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Application\Configuration\Configuration;
use Scenario\Core\Runtime\Metadata\ParameterType;
use Scenario\Core\Runtime\ScenarioDefinition;
use Scenario\Core\Runtime\ScenarioRegistry;
use Scenario\Symfony\Command\ScenarioApplyCommand;
use Scenario\Symfony\Console\Output;
use Scenario\Symfony\Runtime\ProcessRunnerInterface;
use Scenario\Symfony\Tests\Files\ValidScenario;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use const PHP_BINARY;

#[CoversClass(ScenarioApplyCommand::class)]
#[UsesClass(Output::class)]
#[Group('command')]
#[Medium]
final class ScenarioApplyCommandTest extends TestCase
{
    use ScenarioCommand;

    public function testCommandIsConfigured(): void
    {
        $command = new ScenarioApplyCommand(
            $this->createMock(ProcessRunnerInterface::class),
            $this->getKernel(),
            $this->getFilesystem(),
        );

        self::assertSame('scenario:apply', $command->getName());
        self::assertTrue($command->getDefinition()->hasOption('up'));
        self::assertTrue($command->getDefinition()->hasOption('down'));
    }

    protected function setUp(): void
    {
        $this->setScenarioConfiguration($this->createMock(Configuration::class));
        ScenarioRegistry::getInstance()->clear();
    }

    protected function tearDown(): void
    {
        ScenarioRegistry::getInstance()->clear();
        $this->setScenarioConfiguration(null);
    }

    public function testExecuteFailsWhenUpAndDownAreUsedTogether(): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [
                new Parameter('myparam', ParameterType::String, 'parameter description', true, false, 'mydefault'),
            ],
        ));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->never())
            ->method('run');

        $command = new ScenarioApplyCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        );

        $input = new ArrayInput([
            'scenario' => 'valid',
            '--up' => true,
            '--down' => true,
        ]);
        $input->bind($command->getDefinition());
        $input->setInteractive(false);

        $output = new BufferedOutput();

        self::assertSame(Command::FAILURE, $command->run($input, $output));
        self::assertStringContainsString('You can just use either up or down scenarios.', $output->fetch());
    }

    public function testExecuteFailsWhenNoScenariosAreRegistered(): void
    {
        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->never())
            ->method('run');

        $command = new ScenarioApplyCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        );

        $input = new ArrayInput([]);
        $input->bind($command->getDefinition());
        $input->setInteractive(false);

        $output = new BufferedOutput();

        self::assertSame(Command::FAILURE, $command->run($input, $output));
        self::assertStringContainsString('No scenarios were found, please create one.', $output->fetch());
    }

    public function testExecuteRunsProcessRunnerForDirectGivenScenarioUp(): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [],
        ));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    '/project/vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--up',
                    '--force',
                    '--quiet',
                ],
                '/project',
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $command = new ScenarioApplyCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        );

        $input = new ArrayInput([
            'scenario' => 'valid',
        ]);
        $input->bind($command->getDefinition());
        $input->setInteractive(false);

        $output = new BufferedOutput();

        self::assertSame(Command::SUCCESS, $command->run($input, $output));
        self::assertStringContainsString(
            'Scenario "' . ValidScenario::class . '::up" was applied successfully.',
            $output->fetch(),
        );
    }

    public function testExecuteRunsProcessRunnerForDirectGivenScenarioWithParameters(): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [
                new Parameter('myparam', ParameterType::String, required: true),
            ],
        ));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    '/project/vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--up',
                    '--parameter=myparam=hello',
                    '--force',
                    '--quiet',
                ],
                '/project',
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $command = new ScenarioApplyCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        );

        $input = new ArrayInput([
            'scenario' => 'valid',
            '--parameter' => ['myparam=hello'],
        ]);
        $input->bind($command->getDefinition());
        $input->setInteractive(false);

        self::assertSame(Command::SUCCESS, $command->run($input, new BufferedOutput()));
    }

    public function testExecuteRunsProcessRunnerForChoosenScenarioUp(): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [],
        ));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    '/project/vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--up',
                    '--force',
                    '--quiet',
                ],
                '/project',
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $tester = new CommandTester(
            new ScenarioApplyCommand(
                $runner,
                $this->getKernel(),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['0']);

        self::assertSame(Command::SUCCESS, $tester->execute([], ['interactive' => true]));
    }

    public function testExecuteFallsBackToChoiceWhenGivenScenarioIsUnknown(): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [],
        ));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    '/project/vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--up',
                    '--force',
                    '--quiet',
                ],
                '/project',
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $tester = new CommandTester(
            new ScenarioApplyCommand(
                $runner,
                $this->getKernel(),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['0']);

        self::assertSame(Command::SUCCESS, $tester->execute(['scenario' => 'unknown'], ['interactive' => true]));
        self::assertStringContainsString('Given scenario [unknown] is not registered.', $tester->getDisplay());
    }

    public function testExecuteRunsProcessRunnerForChoosenScenarioWithParametersDown(): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [
                new Parameter(
                    name: 'myBool',
                    type: ParameterType::Boolean,
                    required: true,
                ),
                new Parameter(
                    name: 'myInts',
                    type: ParameterType::Integer,
                    required: true,
                    repeatable: true,
                ),
            ],
        ));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    '/project/vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--down',
                    '--parameter=myBool=yes',
                    '--parameter=myInts=5',
                    '--parameter=myInts=3',
                    '--audit',
                    '--force',
                    '--quiet',
                ],
                '/project',
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $tester = new CommandTester(
            new ScenarioApplyCommand(
                $runner,
                $this->getKernel(),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['0', 'yes', '5', 'y', '3', 'n']);

        self::assertSame(Command::SUCCESS, $tester->execute(['--down' => true, '--audit' => true], ['interactive' => true]));
    }

    public function testExecuteRunsProcessRunnerForChoosenScenarioWithOptionalParametersDown(): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [
                new Parameter(
                    name: 'myString',
                    type: ParameterType::String,
                    required: false,
                ),
                new Parameter(
                    name: 'myInts',
                    type: ParameterType::Integer,
                    required: false,
                    repeatable: true,
                ),
            ],
        ));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    '/project/vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--down',
                    '--parameter=myString=',
                    '--parameter=myInts=5',
                    '--parameter=myInts=3',
                    '--force',
                    '--quiet',
                ],
                '/project',
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $tester = new CommandTester(
            new ScenarioApplyCommand(
                $runner,
                $this->getKernel(),
                $this->getFilesystem(),
            ),
        );
        $tester->setInputs(['0', '', '5', 'y', '3', 'n']);

        self::assertSame(Command::SUCCESS, $tester->execute(['--down' => true], ['interactive' => true]));
    }

    public function testExecuteReturnsFailureWhenProcessRunnerFails(): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ValidScenario::class,
            new AsScenario('valid'),
            [],
        ));

        $runner = $this->createMock(ProcessRunnerInterface::class);
        $runner->expects($this->once())
            ->method('run')
            ->with(
                [
                    PHP_BINARY,
                    '/project/vendor/bin/scenario',
                    'apply',
                    ValidScenario::class,
                    '--down',
                    '--force',
                    '--quiet',
                ],
                '/project',
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(false);

        $command = new ScenarioApplyCommand(
            $runner,
            $this->getKernel(),
            $this->getFilesystem(),
        );

        $input = new ArrayInput([
            'scenario' => 'valid',
            '--down' => true,
        ]);
        $input->bind($command->getDefinition());
        $input->setInteractive(false);

        self::assertSame(Command::FAILURE, $command->run($input, new BufferedOutput()));
    }

    private function setScenarioConfiguration(?Configuration $configuration): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('configuration');
        $property->setValue(null, $configuration);
    }
}
