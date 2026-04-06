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
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\MessageConsumerMaxAttemptsException;

#[CoversClass(MessageConsumerMaxAttemptsException::class)]
#[Group('runtime')]
#[Small]
final class MessageConsumerMaxAttemptsExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new MessageConsumerMaxAttemptsException(5, 'async');

        self::assertSame(
            'tried 5 times to drain the queue for receiver "async", please check for message failures',
            $exception->getMessage(),
        );
    }
}
