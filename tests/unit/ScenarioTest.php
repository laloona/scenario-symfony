<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Runtime\CommandRunnerInterface;
use Stateforge\Scenario\Symfony\Runtime\ConfigResolverInterface;
use Stateforge\Scenario\Symfony\Runtime\Messenger\MessageConsumerInterface;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessRunnerInterface;
use Stateforge\Scenario\Symfony\Scenario;
use Stateforge\Scenario\Symfony\Tests\Files\ValidScenario;
use stdClass;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function realpath;
use function sys_get_temp_dir;
use function uniqid;
use const DIRECTORY_SEPARATOR;

#[CoversClass(Scenario::class)]
#[Group('scenario')]
#[Medium]
final class ScenarioTest extends TestCase
{
    use PathHelper;

    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/scenario-' . uniqid('', true);
        (new Filesystem())->mkdir($this->projectDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->projectDir);
    }

    public function testNeedsInjectsDependenciesAndHelperMethodsDelegateCorrectly(): void
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDir);

        $configResolver = $this->createMock(ConfigResolverInterface::class);
        $configResolver->expects($this->once())
            ->method('get')
            ->with('feature.enabled', false)
            ->willReturn(true);

        $commandRunner = $this->createMock(CommandRunnerInterface::class);
        $commandRunner->expects($this->once())
            ->method('execute')
            ->with('demo:run', ['mode' => 'fast']);

        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects($this->once())
            ->method('run')
            ->with(
                ['bin/console', 'about'],
                $this->projectDir,
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(true);

        $messageConsumer = $this->createMock(MessageConsumerInterface::class);
        $messageConsumer->expects($this->once())
            ->method('consume')
            ->with('async');

        $filesystem = new Filesystem();

        $repository = self::createStub(EntityRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(stdClass::class)
            ->willReturn($repository);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $event = new stdClass();
        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, 'demo.event');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $message = new stdClass();
        $messageBus->expects($this->once())
            ->method('dispatch')
            ->with($message)
            ->willReturn(new Envelope($message));

        $scenario = new ValidScenario();
        $scenario->needs(
            $kernel,
            $configResolver,
            $commandRunner,
            $processRunner,
            $messageConsumer,
            $filesystem,
            $entityManager,
            $eventDispatcher,
            $messageBus,
        );

        self::assertSame($this->projectDir, $scenario->testRootDir());
        self::assertSame($filesystem, $scenario->testFilesystem());
        self::assertSame($entityManager, $scenario->testEntityManager());
        self::assertSame($repository, $scenario->testRepository(stdClass::class));
        self::assertTrue($scenario->testConfig('feature.enabled', false));

        $relativeDirectory = 'var/cache/scenario';
        $absoluteDirectory = $scenario->testAbsoluteDir($relativeDirectory, true);
        self::assertSame(realpath($this->projectDir . '/var/cache/scenario'), $absoluteDirectory);

        $absoluteFile = $scenario->testAbsoluteFile('var/log/scenario.log', true);
        self::assertIsString($absoluteFile);

        $expectedFile = realpath($this->projectDir . '/var/log');
        self::assertNotFalse($expectedFile);

        self::assertSame(
            $this->normalizePath($expectedFile . DIRECTORY_SEPARATOR . 'scenario.log'),
            $this->normalizePath($absoluteFile),
        );

        $scenario->testCommand('demo:run', ['mode' => 'fast']);
        $scenario->testEvent($event, 'demo.event');
        $scenario->testMessage($message);
        $scenario->testConsumer('async');

        self::assertTrue($scenario->testShell(['bin/console', 'about']));
    }

    public function testAbsoluteHelpersSupportAbsolutePathsAndShellCanFail(): void
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn($this->projectDir);

        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects($this->once())
            ->method('run')
            ->with(
                ['bin/console', 'fail'],
                $this->projectDir,
                self::isInstanceOf(OutputInterface::class),
            )
            ->willReturn(false);

        $scenario = new ValidScenario();
        $scenario->needs(
            $kernel,
            self::createStub(ConfigResolverInterface::class),
            self::createStub(CommandRunnerInterface::class),
            $processRunner,
            self::createStub(MessageConsumerInterface::class),
            new Filesystem(),
            self::createStub(EntityManagerInterface::class),
            self::createStub(EventDispatcherInterface::class),
            self::createStub(MessageBusInterface::class),
        );

        $absoluteDirectory = $this->projectDir . '/already/absolute';
        (new Filesystem())->mkdir($absoluteDirectory);

        self::assertSame(realpath($absoluteDirectory), $scenario->testAbsoluteDir($absoluteDirectory, false));
        self::assertFalse($scenario->testAbsoluteFile('missing/file.txt', false));
        self::assertFalse($scenario->testShell(['bin/console', 'fail']));
    }
}
