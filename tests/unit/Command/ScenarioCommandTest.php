<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

#[CoversClass(ScenarioCommand::class)]
#[Group('command')]
#[Small]
final class ScenarioCommandTest extends TestCase
{
    public function testIsEnabledReturnsTrueWhenEnvironmentIsAllowedAndScenarioIsInstalled(): void
    {
        $kernel = self::createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('dev');
        $kernel->method('getProjectDir')->willReturn('/project');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->once())
            ->method('exists')
            ->with('/project/config/packages/scenario.yaml')
            ->willReturn(true);

        self::assertTrue(new TestScenarioCommand($kernel, $filesystem)->isEnabled());
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
            ->with('/project/config/packages/scenario.yaml')
            ->willReturn(false);

        self::assertFalse(new TestScenarioCommand($kernel, $filesystem)->isEnabled());
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

        self::assertSame('/project/vendor/stateforge/scenario-symfony/blueprint/demo.yaml', $command->getBlueprintPublic('demo.yaml'));
        self::assertSame('/project/vendor/bin/scenario', $command->getCliPathPublic());
    }
}

final class TestScenarioCommand extends ScenarioCommand
{
    public function isAllowedPublic(): bool
    {
        return $this->isAllowed();
    }

    public function getBlueprintPublic(string $blueprintFile): string
    {
        return $this->getBlueprint($blueprintFile);
    }

    public function getCliPathPublic(): string
    {
        return $this->getCliPath();
    }
}
