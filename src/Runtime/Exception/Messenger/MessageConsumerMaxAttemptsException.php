<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime\Exception\Messenger;

use Stateforge\Scenario\Core\Runtime\Exception\Exception;
use function sprintf;

final class MessageConsumerMaxAttemptsException extends Exception
{
    public function __construct(int $attempts, string $receiver)
    {
        parent::__construct(
            sprintf(
                'tried %d times to drain the queue for receiver "%s", please check for message failures',
                $attempts,
                $receiver,
            ),
        );
    }
}
