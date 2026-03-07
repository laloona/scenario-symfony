<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Runtime;

use Scenario\Core\Application;
use Scenario\Core\Runtime\ScenarioRegistry;
use Scenario\Symfony\Runtime\CompilerPass\RegisterBuilderPass;
use Scenario\Symfony\Runtime\CompilerPass\RegisterDatabaseHandlerPass;
use Scenario\Symfony\Runtime\CompilerPass\RegisterScenarioPass;
use Scenario\Symfony\Runtime\Exception\ApplicationException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use function array_keys;
use function dirname;
use function escapeshellarg;
use function exec;
use function is_dir;

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
            exec('rm -rf ' . escapeshellarg(dirname($cacheDir)));
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
            new RegisterDatabaseHandlerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            100,
        );
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
