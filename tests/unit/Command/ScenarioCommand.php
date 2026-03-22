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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

trait ScenarioCommand
{
    private function getKernel(): KernelInterface
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('dev');
        $kernel->method('getProjectDir')->willReturn('/project');

        return $kernel;
    }

    private function getFilesystem(): Filesystem
    {
        $filesystem = self::createStub(Filesystem::class);
        $filesystem->method('exists')->willReturn(true);

        return $filesystem;
    }
}
