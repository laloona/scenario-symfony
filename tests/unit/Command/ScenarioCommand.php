<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Command;

use ReflectionClass;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use function preg_replace;

trait ScenarioCommand
{
    private function getKernel(string $projectDir = '/project'): KernelInterface
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('dev');
        $kernel->method('getProjectDir')->willReturn($projectDir);

        return $kernel;
    }

    private function getFilesystem(): Filesystem
    {
        $filesystem = self::createStub(Filesystem::class);
        $filesystem->method('exists')->willReturn(true);

        return $filesystem;
    }

    private function setScenarioConfiguration(?Configuration $configuration): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('configuration');
        $property->setValue(null, $configuration);
    }

    private function setApplicationRootDir(?string $rootDir): void
    {
        $property = (new ReflectionClass(Application::class))->getProperty('rootDir');
        $property->setValue(null, $rootDir);
    }

    private function formatOutput(string $string): string
    {
        return preg_replace('/\s+/', ' ', $string);
    }
}
