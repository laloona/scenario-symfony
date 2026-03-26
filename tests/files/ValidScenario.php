<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Tests\Files;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Scenario\Symfony\Scenario;
use Symfony\Component\Filesystem\Filesystem;

final class ValidScenario extends Scenario
{
    public function up(): void
    {
    }

    public function testRootDir(): string
    {
        return $this->rootDir();
    }

    public function testAbsoluteDir(string $directory, bool $create): string|false
    {
        return $this->absoluteDir($directory, $create);
    }

    public function testAbsoluteFile(string $file, bool $create): string|false
    {
        return $this->absoluteFile($file, $create);
    }

    public function testConfig(string $key, mixed $default = null): mixed
    {
        return $this->config($key, $default);
    }

    /**
     * @param array<string, string> $params
     */
    public function testCommand(string $command, array $params = []): void
    {
        $this->command($command, $params);
    }

    public function testFilesystem(): Filesystem
    {
        return $this->filesystem();
    }

    public function testEntityManager(): EntityManagerInterface
    {
        return $this->entityManager();
    }

    /**
     * @template T of object
     * @param class-string<T> $entity
     * @return EntityRepository<T>
     */
    public function testRepository(string $entity): EntityRepository
    {
        return $this->repository($entity);
    }

    public function testEvent(object $event, ?string $eventName = null): void
    {
        $this->event($event, $eventName);
    }

    public function testMessage(object $message): void
    {
        $this->message($message);
    }

    public function testConsumer(string $receiver): void
    {
        $this->consumer($receiver);
    }

    /**
     * @param list<string> $cli
     */
    public function testShell(array $cli): bool
    {
        return $this->shell($cli);
    }
}
