<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime;

use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use Stateforge\Scenario\Symfony\Runtime\CompilerPass\RegisterBuilderPass;
use Stateforge\Scenario\Symfony\Runtime\CompilerPass\RegisterScenarioPass;
use Stateforge\Scenario\Symfony\Runtime\Exception\ApplicationException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use function array_keys;
use function dirname;
use function is_dir;
use const DIRECTORY_SEPARATOR;

final class ScenarioKernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return Application::getRootDir();
    }

    public function getCacheDir(): string
    {
        $config = Application::config();
        if ($config === null) {
            throw new ApplicationException();
        }

        $cacheDir = $this->getProjectDir() . DIRECTORY_SEPARATOR .
            $config->getCacheDirectory() . DIRECTORY_SEPARATOR .
            'symfony' . DIRECTORY_SEPARATOR .
            $config->getCacheKey();

        // when cache dir does not exist we have probably old caches, delete them
        if (is_dir($cacheDir) === false
            && is_dir(dirname($cacheDir)) === true) {
            (new Filesystem())->remove(dirname($cacheDir));
        }

        return $cacheDir;
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $parameter = [];
        $definitions = ScenarioRegistry::getInstance()->all();
        foreach ($definitions as $definition) {
            $parameter[$definition->class] = $definition->class;
        }
        $container->setParameter('scenario.definitions', array_keys($parameter));

        $container->addCompilerPass(
            new RegisterScenarioPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            90,
        );
        $container->addCompilerPass(
            new RegisterBuilderPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            80,
        );
    }
}
