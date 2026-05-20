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
use Symfony\Component\Validator\Constraints\Hostname;

#[AsParameterType('Validates hostnames.')]
final class HostnameType extends StringTypeDefinition
{
    /**
     * @return list<Constraint>
     */
    protected function constraints(): array
    {
        return [
            new Hostname(),
        ];
    }
}
