<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Scenario\Core\Scenario as CoreScenario;
use Scenario\Symfony\Runtime\CommandRunner;
use Scenario\Symfony\Runtime\ConfigResolver;
use Symfony\Component\Filesystem\Filesystem;

abstract class Scenario extends CoreScenario
{
    public function __construct(
        private readonly ConfigResolver $configResolver,
        private readonly CommandRunner $commandRunner,
        private readonly Filesystem $filesystem,
        private readonly EntityManagerInterface $entityManager,
    ) {
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
}
