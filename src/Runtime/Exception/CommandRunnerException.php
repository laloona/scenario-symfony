<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime\Exception;

use Stateforge\Scenario\Core\Runtime\Exception\Exception;
use Throwable;
use function get_class;
use function sprintf;

final class CommandRunnerException extends Exception
{
    public function __construct(string $command, Throwable $throwable)
    {
        parent::__construct(
            sprintf(
                'Command [%s] throwed the following exception: %s %s',
                $command,
                get_class($throwable),
                $throwable->getMessage(),
            ),
            $throwable->getCode(),
            $throwable,
        );
    }
}
