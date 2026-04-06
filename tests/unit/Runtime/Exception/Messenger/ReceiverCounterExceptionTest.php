<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Runtime\Exception\Messenger;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\ReceiverCounterException;

#[CoversClass(ReceiverCounterException::class)]
#[Group('runtime')]
#[Small]
final class ReceiverCounterExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new ReceiverCounterException('async');

        self::assertSame(
            'could not determine the number of pending messages for receiver "async" from messenger:stats output',
            $exception->getMessage(),
        );
    }
}
