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
use Stateforge\Scenario\Core\Attribute\ParameterTypeCondition;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Country;

#[AsParameterType('Validates ISO 3166-1 alpha-3 country codes. Requires the intl extension.')]
#[ParameterTypeCondition(IntlExtensionCondition::class)]
final class CountryAlpha3Type extends StringTypeDefinition
{
    /**
     * @return list<Constraint>
     */
    protected function constraints(): array
    {
        return [
            new Country(alpha3: true),
        ];
    }
}
