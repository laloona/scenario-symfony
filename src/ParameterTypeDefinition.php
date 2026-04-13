<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony;

use Stateforge\Scenario\Core\ParameterTypeDefinition as CoreParameterTypeDefinition;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function count;

abstract class ParameterTypeDefinition extends CoreParameterTypeDefinition
{
    private static ?ValidatorInterface $validator = null;

    final public function cast(mixed $value): string|int|float|bool|null
    {
        $violations = $this->validator()->validate($value, $this->constraints());

        if (count($violations) === 0) {
            return $this->valueType($value)->value;
        }

        return null;
    }

    private function validator(): ValidatorInterface
    {
        if (self::$validator === null) {
            self::$validator = Validation::createValidator();
        }

        return self::$validator;
    }

    /**
     * @return list<Constraint>
     */
    abstract protected function constraints(): array;
}
