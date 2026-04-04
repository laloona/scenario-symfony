<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Service\Attribute\Required;
use function array_filter;
use function array_map;
use function array_values;
use function in_array;
use const DIRECTORY_SEPARATOR;

abstract class ScenarioCommand extends Command
{
    /** @var list<string> */
    private array $allowedEnvs = ['dev', 'test'];

    public function __construct(
        private KernelInterface $kernel,
        private Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    /**
     * Symfony injects parameters as array<int|string, mixed>.
     *
     * @param array<int|string, string> $allowedEnvs
     */
    #[Required]
    public function setAllowedEnvs(array $allowedEnvs): void
    {
        $allowedEnvs = array_values($allowedEnvs);
        $allowedEnvs = array_values(array_filter(
            array_map(static fn ($v): string => $v, $allowedEnvs),
            static fn (string $v): bool => $v !== '',
        ));

        $this->allowedEnvs = $allowedEnvs;
    }

    public function isEnabled(): bool
    {
        return $this->isAllowed() === true
            && $this->isInstalled() === true;
    }

    final protected function isAllowed(): bool
    {
        return in_array($this->getKernel()->getEnvironment(), $this->allowedEnvs, true);
    }

    final protected function isInstalled(): bool
    {
        return $this->getFilesystem()->exists(
            $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR .
            'config' . DIRECTORY_SEPARATOR .
            'packages'. DIRECTORY_SEPARATOR .
            'scenario.yaml',
        );
    }

    final protected function getBlueprint(string $blueprintFile): string
    {
        return $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR .
            'vendor' . DIRECTORY_SEPARATOR .
            'scenario' . DIRECTORY_SEPARATOR .
            'symfony' . DIRECTORY_SEPARATOR .
            'blueprint' . DIRECTORY_SEPARATOR . $blueprintFile;
    }

    final protected function getKernel(): KernelInterface
    {
        return $this->kernel;
    }

    final protected function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    final protected function getCliPath(): string
    {
        return $this->getKernel()->getProjectDir() . DIRECTORY_SEPARATOR .
            'vendor' . DIRECTORY_SEPARATOR .
            'bin' . DIRECTORY_SEPARATOR .
            'scenario';
    }
}
