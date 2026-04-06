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
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\UnknownReceiverException;

#[CoversClass(UnknownReceiverException::class)]
#[Group('runtime')]
#[Small]
final class UnknownReceiverExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new UnknownReceiverException('async');

        self::assertSame(
            'receiver "async" is not configured',
            $exception->getMessage(),
        );
    }
}
