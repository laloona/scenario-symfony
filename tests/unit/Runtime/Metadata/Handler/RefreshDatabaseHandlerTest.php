<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Tests\Unit\Runtime\Metadata\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Scenario\Core\Attribute\RefreshDatabase;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Application\Configuration\Configuration;
use Scenario\Core\Runtime\Application\Configuration\Value\ConnectionValue;
use Scenario\Core\Runtime\Metadata\AttributeContext;
use Scenario\Core\Runtime\Metadata\ExecutionType;
use Scenario\Symfony\Runtime\CommandRunner;
use Scenario\Symfony\Runtime\Exception\CommandRunnerException;
use Scenario\Symfony\Runtime\Metadata\Handler\RefreshDatabaseHandler;
use Symfony\Bundle\FrameworkBundle\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[CoversClass(RefreshDatabaseHandler::class)]
#[UsesClass(CommandRunner::class)]
#[UsesClass(CommandRunnerException::class)]
#[Group('runtime')]
#[Small]
final class RefreshDatabaseHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setScenarioConfiguration(null);
        $this->setCommandRunnerApplication(null);
    }

    public function testHandleExecutesRefreshCommandWithConnectionAndConfiguration(): void
    {
        $configuration = self::createStub(Configuration::class);
        $configuration->method('getConnections')
            ->willReturn([
                'default' => new ConnectionValue('default', 'doctrine.yaml'),
            ]);
        $this->setScenarioConfiguration($configuration);

        $command = $this->createPartialMock(Command::class, ['execute']);
        $command->expects(self::once())
            ->method('execute')
            ->willReturnCallback(function (InputInterface $input, OutputInterface $output) {
                self::assertSame('default', $input->getOption('connection'));
                self::assertSame('doctrine.yaml', $input->getOption('configuration'));
                return Command::SUCCESS;
            });
        $command->setDefinition(new InputDefinition([]));
        $command->addOption('connection', null, InputOption::VALUE_REQUIRED);
        $command->addOption('configuration', null, InputOption::VALUE_REQUIRED);

        $application = $this->createMock(SymfonyApplication::class);
        $application->expects($this->once())
            ->method('find')
            ->with('scenario:migrations:refresh')
            ->willReturn($command);
        $this->setCommandRunnerApplication($application);

        new RefreshDatabaseHandler(new CommandRunner(self::createStub(KernelInterface::class)))
            ->handle(
                AttributeContext::getInstance(
                    self::class,
                    null,
                    ExecutionType::Up,
                    false,
                    null,
                ),
                new RefreshDatabase('default'),
            );
    }

    public function testHandleSkipsCommandExecutionOnDryRun(): void
    {
        $application = $this->createMock(SymfonyApplication::class);
        $application->expects($this->never())->method('find');
        $this->setCommandRunnerApplication($application);

        new RefreshDatabaseHandler(new CommandRunner(self::createStub(KernelInterface::class)))
            ->handle(
                AttributeContext::getInstance(
                    self::class,
                    'testHandleSkipsCommandExecutionOnDryRun',
                    ExecutionType::Up,
                    true,
                    null,
                ),
                new RefreshDatabase('default'),
            );

        self::assertSame(
            [RefreshDatabaseHandler::class . '{"connection":"default"}'],
            AttributeContext::getInstance(
                self::class,
                'testHandleSkipsCommandExecutionOnDryRun',
                ExecutionType::Up,
                true,
                null,
            )->getAudits(),
        );
    }

    public function testHandleSkipsCommandExecutionOnDown(): void
    {
        $application = $this->createMock(SymfonyApplication::class);
        $application->expects($this->never())->method('find');
        $this->setCommandRunnerApplication($application);

        new RefreshDatabaseHandler(new CommandRunner(self::createStub(KernelInterface::class)))
            ->handle(
                AttributeContext::getInstance(
                    self::class,
                    'testHandleSkipsCommandExecutionOnDown',
                    ExecutionType::Down,
                    false,
                    null,
                ),
                new RefreshDatabase('default'),
            );

        self::assertSame(
            [],
            AttributeContext::getInstance(
                self::class,
                'testHandleSkipsCommandExecutionOnDown',
                ExecutionType::Down,
                false,
                null,
            )->getAudits(),
        );
    }

    private function setScenarioConfiguration(?Configuration $configuration): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('configuration');
        $property->setValue(null, $configuration);
    }

    private function setCommandRunnerApplication(?SymfonyApplication $application): void
    {
        $property = (new ReflectionClass(CommandRunner::class))->getProperty('application');
        $property->setValue(null, $application);
    }
}
