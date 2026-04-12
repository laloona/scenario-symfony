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

use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\FloatType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

abstract class FloatTypeDefinition extends ParameterTypeDefinition
{
    protected function getValueType(mixed $value): FloatType
    {
        return new FloatType($value);
    }
}
