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
use Stateforge\Scenario\Symfony\Runtime\Exception\MessageConsumerException;

#[CoversClass(MessageConsumerException::class)]
#[Group('runtime')]
#[Small]
final class MessageConsumerExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new MessageConsumerException(
            'async',
            'some error happened',
        );

        self::assertSame(
            'Messenger Consumer for receiver [async] failed: some error happened',
            $exception->getMessage(),
        );
    }
}
