<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Runtime\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Runtime\Exception\CommandRunnerResultException;

#[CoversClass(CommandRunnerResultException::class)]
#[Group('runtime')]
#[Small]
final class CommandRunnerResultExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new CommandRunnerResultException(
            'my:command',
            1,
        );

        self::assertSame(
            'command [my:command] failed with exit code: 1',
            $exception->getMessage(),
        );
    }
}
