<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Tests\Unit\Command;

use ReflectionClass;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Application\Configuration\Configuration;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

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
}
