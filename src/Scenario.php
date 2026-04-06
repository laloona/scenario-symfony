<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Stateforge\Scenario\Core\Scenario as CoreScenario;
use Stateforge\Scenario\Symfony\Runtime\CommandRunnerInterface;
use Stateforge\Scenario\Symfony\Runtime\ConfigResolverInterface;
use Stateforge\Scenario\Symfony\Runtime\Messenger\MessageConsumerInterface;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessRunnerInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\Required;
use function basename;
use function dirname;
use function file_exists;
use function mkdir;
use function realpath;
use function str_replace;
use function str_starts_with;
use const DIRECTORY_SEPARATOR;

abstract class Scenario extends CoreScenario
{
    private string $rootDir;

    private ConfigResolverInterface $configResolver;

    private CommandRunnerInterface $commandRunner;

    private ProcessRunnerInterface $processRunner;

    private MessageConsumerInterface $messageConsumer;

    private Filesystem $filesystem;

    private EntityManagerInterface $entityManager;

    private EventDispatcherInterface $eventDispatcher;

    private MessageBusInterface $messageBus;

    #[Required]
    public function needs(
        KernelInterface $kernel,
        ConfigResolverInterface $configResolver,
        CommandRunnerInterface $commandRunner,
        ProcessRunnerInterface $processRunner,
        MessageConsumerInterface $messageConsumer,
        Filesystem $filesystem,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        MessageBusInterface $messageBus,
    ): void {
        $this->rootDir = $kernel->getProjectDir();
        $this->configResolver = $configResolver;
        $this->commandRunner = $commandRunner;
        $this->processRunner = $processRunner;
        $this->messageConsumer = $messageConsumer;
        $this->filesystem = $filesystem;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->messageBus = $messageBus;
    }

    final protected function rootDir(): string
    {
        return $this->rootDir;
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    final protected function absoluteDir(string $directory, bool $create): string|false
    {
        $absolute = str_starts_with($this->normalizePath($directory), $this->normalizePath($this->rootDir))
            ? $directory
            : $this->rootDir . DIRECTORY_SEPARATOR . $directory;

        if ($create === true
            && file_exists($absolute) === false) {
            mkdir($absolute, 0777, true);
        }

        return realpath($absolute);
    }

    final protected function absoluteFile(string $file, bool $create): string|false
    {
        $directory = $this->absoluteDir(dirname($file), $create);
        if ($directory === false) {
            return false;
        }

        return $directory . DIRECTORY_SEPARATOR . basename($file);
    }

    final protected function config(string $key, mixed $default = null): mixed
    {
        return $this->configResolver->get($key, $default);
    }

    /**
     * @param array<string, string> $params
     */
    final protected function command(string $command, array $params = []): void
    {
        $this->commandRunner->execute($command, $params);
    }

    final protected function filesystem(): Filesystem
    {
        return $this->filesystem;
    }

    final protected function entityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @template T of object
     * @param class-string<T> $entity
     * @return EntityRepository<T>
     */
    final protected function repository(string $entity): EntityRepository
    {
        /** @var EntityRepository<T> $repository */
        $repository = $this->entityManager->getRepository($entity);
        return $repository;
    }

    final protected function event(object $event, ?string $eventName = null): void
    {
        $this->eventDispatcher->dispatch($event, $eventName);
    }

    final protected function message(object $message): void
    {
        $this->messageBus->dispatch($message);
    }

    final protected function consumer(string $receiver): void
    {
        $this->messageConsumer->consume($receiver);
    }

    /**
     * @param list<string> $cli
     */
    final protected function shell(array $cli): bool
    {
        return $this->processRunner->run($cli, $this->rootDir, new NullOutput());
    }
}
