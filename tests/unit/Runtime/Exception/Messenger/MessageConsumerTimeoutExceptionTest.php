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
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\MessageConsumerTimeoutException;

#[CoversClass(MessageConsumerTimeoutException::class)]
#[Group('runtime')]
#[Small]
final class MessageConsumerTimeoutExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new MessageConsumerTimeoutException('async');

        self::assertSame(
            'queue for receiver "async" could not be drained before the timeout was reached',
            $exception->getMessage(),
        );
    }
}
