<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Runtime\Process;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Runtime\Process\ProcessFactory;
use Symfony\Component\Process\Process;

#[CoversClass(ProcessFactory::class)]
#[Group('runtime')]
#[Small]
final class ProcessFactoryTest extends TestCase
{
    public function testCreateReturnsProcessForArgumentsAndDirectory(): void
    {
        $process = (new ProcessFactory())->create(['php', 'bin/console'], '/project');

        self::assertInstanceOf(Process::class, $process);
        self::assertSame('/project', $process->getWorkingDirectory());
        self::assertStringContainsString('php', $process->getCommandLine());
        self::assertStringContainsString('bin/console', $process->getCommandLine());
    }
}
