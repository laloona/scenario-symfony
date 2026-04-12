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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Negative;
use Symfony\Component\Validator\Constraints\Type;

final class NegativeFloatType extends FloatTypeDefinition
{
    /**
     * @return list<Constraint>
     */
    protected function constraints(): array
    {
        return [
            new Type('float'),
            new Negative(),
        ];
    }
}
