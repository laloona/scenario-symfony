<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\ReceiverCounterAwareException;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\UnknownReceiverException;
use Stateforge\Scenario\Symfony\Runtime\Messenger\MessageCounter;
use stdClass;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;

#[CoversClass(MessageCounter::class)]
#[CoversClass(UnknownReceiverException::class)]
#[CoversClass(ReceiverCounterAwareException::class)]
#[Group('runtime')]
#[Small]
final class MessageCounterTest extends TestCase
{
    public function testReturnsMessageCount(): void
    {
        $receiver = $this->createMock(MessageCountAwareInterface::class);
        $receiver->method('getMessageCount')->willReturn(5);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('async')->willReturn(true);
        $container->method('get')->with('async')->willReturn($receiver);

        self::assertSame(5, (new MessageCounter($container))->count('async'));
    }

    public function testThrowsWhenReceiverDoesNotExist(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('async')->willReturn(false);

        $this->expectException(UnknownReceiverException::class);
        (new MessageCounter($container))->count('async');
    }

    public function testThrowsWhenReceiverDoesNotSupportCounting(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('async')->willReturn(true);
        $container->method('get')->with('async')->willReturn(new stdClass());

        $this->expectException(ReceiverCounterAwareException::class);
        (new MessageCounter($container))->count('async');
    }
}
