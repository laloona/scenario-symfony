<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\DefaultConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\LoadedConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\ConnectionValue;
use Stateforge\Scenario\Symfony\Runtime\CommandRunner;
use Stateforge\Scenario\Symfony\Runtime\DatabaseRefreshExecutor;
use Symfony\Bundle\FrameworkBundle\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[CoversClass(DatabaseRefreshExecutor::class)]
#[UsesClass(Application::class)]
#[UsesClass(CommandRunner::class)]
#[UsesClass(ConnectionValue::class)]
#[UsesClass(DefaultConfiguration::class)]
#[UsesClass(LoadedConfiguration::class)]
#[UsesClass(RefreshDatabase::class)]
#[Group('runtime')]
#[Small]
final class DatabaseRefreshExecutorTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflection = new ReflectionClass(Application::class);
        $property = $reflection->getProperty('configuration');
        $property->setValue(null, null);

        $reflection = new ReflectionClass(CommandRunner::class);
        $property = $reflection->getProperty('application');
        $property->setValue(null, null);
    }

    public function testExecuteRunsRefreshCommandWithConfiguredConnection(): void
    {
        $configuration = new LoadedConfiguration(new DefaultConfiguration());
        $configuration->setConnections([
            'main' => new ConnectionValue('main', 'config/packages/doctrine.yaml'),
        ]);
        $this->setApplicationConfiguration($configuration);

        $command = $this->createPartialMock(Command::class, ['run']);
        $command->expects(self::once())
            ->method('run')
            ->with(
                self::callback(static function (InputInterface $input): bool {
                    return $input->getParameterOption('--connection') === 'main'
                        && $input->getParameterOption('--configuration') === 'config/packages/doctrine.yaml';
                }),
                self::anything(),
            )
            ->willReturn(Command::SUCCESS);

        $application = $this->createMock(SymfonyApplication::class);
        $application->expects(self::once())
            ->method('find')
            ->with('scenario:migrations:refresh')
            ->willReturn($command);
        $this->setCommandRunnerApplication($application);

        $commandRunner = new CommandRunner(self::createStub(KernelInterface::class));
        (new DatabaseRefreshExecutor($commandRunner))->execute(new RefreshDatabase('main'));
    }

    public function testExecuteRunsRefreshCommandWithoutConnectionWhenMissing(): void
    {
        $command = $this->createPartialMock(Command::class, ['run']);
        $command->expects(self::once())
            ->method('run')
            ->with(
                self::callback(static function (InputInterface $input): bool {
                    return $input->getParameterOption('--connection') === false
                        && $input->getParameterOption('--configuration') === false;
                }),
                self::anything(),
            )
            ->willReturn(Command::SUCCESS);

        $application = $this->createMock(SymfonyApplication::class);
        $application->expects(self::once())
            ->method('find')
            ->with('scenario:migrations:refresh')
            ->willReturn($command);
        $this->setCommandRunnerApplication($application);

        $commandRunner = new CommandRunner(self::createStub(KernelInterface::class));
        (new DatabaseRefreshExecutor($commandRunner))->execute(new RefreshDatabase(null));
    }

    private function setApplicationConfiguration(LoadedConfiguration $configuration): void
    {
        $reflection = new ReflectionClass(Application::class);
        $property = $reflection->getProperty('configuration');
        $property->setValue(null, $configuration);
    }

    private function setCommandRunnerApplication(?SymfonyApplication $application): void
    {
        $reflection = new ReflectionClass(CommandRunner::class);
        $property = $reflection->getProperty('application');
        $property->setValue(null, $application);
    }
}
