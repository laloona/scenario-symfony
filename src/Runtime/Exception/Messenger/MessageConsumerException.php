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

final class MessageConsumerException extends Exception
{
    public function __construct(string $receiver, string $errorOutput)
    {
        parent::__construct(
            sprintf(
                'messenger consumer for receiver [%s] failed: %s',
                $receiver,
                $errorOutput,
            ),
        );
    }
}
