<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime\Messenger;

use Psr\Container\ContainerInterface;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\ReceiverCounterAwareException;
use Stateforge\Scenario\Symfony\Runtime\Exception\Messenger\UnknownReceiverException;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;

final class MessageCounter implements MessageCounterInterface
{
    public function __construct(
        private ContainerInterface $receiverLocator,
    ) {
    }

    public function count(string $receiver): int
    {
        if ($this->receiverLocator->has($receiver) === false) {
            throw new UnknownReceiverException($receiver);
        }

        $transport = $this->receiverLocator->get($receiver);
        if (! $transport instanceof MessageCountAwareInterface) {
            throw new ReceiverCounterAwareException($receiver);
        }

        return $transport->getMessageCount();
    }
}
