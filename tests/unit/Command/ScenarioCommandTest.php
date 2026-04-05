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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Command\ScenarioCommand;
use Stateforge\Scenario\Symfony\Tests\Files\TestScenarioCommand;
use Stateforge\Scenario\Symfony\Tests\Unit\PathHelper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use const DIRECTORY_SEPARATOR;

#[CoversClass(ScenarioCommand::class)]
#[Group('command')]
#[Small]
final class ScenarioCommandTest extends TestCase
{
    use PathHelper;

    public function testIsEnabledReturnsTrueWhenEnvironmentIsAllowedAndScenarioIsInstalled(): void
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('dev');
        $kernel->method('getProjectDir')->willReturn('/project');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('exists')
            ->with('/project' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'scenario.yaml')
            ->willReturn(true);

        self::assertTrue((new TestScenarioCommand($kernel, $filesystem))->isEnabled());
    }

    public function testSetAllowedEnvsFiltersEmptyValuesBeforeCheckingEnvironment(): void
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('prod');
        $kernel->method('getProjectDir')->willReturn('/project');

        $filesystem = $this->createMock(Filesystem::class);

        $command = new TestScenarioCommand($kernel, $filesystem);
        $command->setAllowedEnvs(['', 'prod', '']);

        self::assertTrue($command->isAllowedPublic());
    }

    public function testIsEnabledReturnsFalseWhenScenarioIsNotInstalled(): void
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('dev');
        $kernel->method('getProjectDir')->willReturn('/project');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('exists')
            ->with($this->normalizePath('/project' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR . 'scenario.yaml'))
            ->willReturn(false);

        self::assertFalse((new TestScenarioCommand($kernel, $filesystem))->isEnabled());
    }

    public function testPathHelpersBuildExpectedProjectPaths(): void
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getProjectDir')->willReturn('/project');
        $kernel->method('getEnvironment')->willReturn('dev');

        $command = new TestScenarioCommand(
            $kernel,
            self::createStub(Filesystem::class),
        );

        self::assertSame(
            '/project/vendor/stateforge/scenario-symfony/blueprint/demo.yaml',
            $this->normalizePath($command->getBlueprintPublic('demo.yaml')),
        );
        self::assertSame(
            '/project/vendor/bin/scenario',
            $this->normalizePath($command->getCliPathPublic()),
        );
    }
}
