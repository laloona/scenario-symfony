<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Runtime\Exception;

use Scenario\Core\Runtime\Exception\Exception;
use function sprintf;

final class ScenarioBuilderException extends Exception
{
    public function __construct(string $scenario)
    {
        parent::__construct(
            sprintf(
                'Unknown scenario %s',
                $scenario,
            ),
        );
    }
}
