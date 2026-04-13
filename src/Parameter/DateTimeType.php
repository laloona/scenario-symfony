<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Parameter;

use Stateforge\Scenario\Core\Attribute\AsParameterType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\DateTime;

#[AsParameterType('Validates date and time values in the format YYYY-MM-DD HH:MM:SS.')]
final class DateTimeType extends StringTypeDefinition
{
    /**
     * @return list<Constraint>
     */
    protected function constraints(): array
    {
        return [
            new DateTime(),
        ];
    }
}
