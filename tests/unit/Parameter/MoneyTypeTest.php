<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Parameter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\FloatType;
use Stateforge\Scenario\Symfony\Parameter\FloatTypeDefinition;
use Stateforge\Scenario\Symfony\Parameter\MoneyType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;

#[CoversClass(MoneyType::class)]
#[UsesClass(FloatTypeDefinition::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(FloatType::class)]
#[UsesClass(Type::class)]
#[UsesClass(PositiveOrZero::class)]
#[UsesClass(Regex::class)]
#[Group('parameter')]
#[Small]
final class MoneyTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new MoneyType();

        self::assertSame('MoneyType', $type->name);
        self::assertSame(MoneyType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForNonNegativeMonetaryValues(): void
    {
        $type = new MoneyType();

        self::assertTrue($type->valid(12.34));
        self::assertTrue($type->valid('12.30'));
        self::assertFalse($type->valid(12.345));
        self::assertFalse($type->valid(-1.0));
    }

    public function testAsStringReturnsMoneyStringAndNullForInvalidInput(): void
    {
        $type = new MoneyType();

        self::assertSame('12.34', $type->asString(12.34));
        self::assertSame('12.3', $type->asString('12.30'));
        self::assertNull($type->asString(12.345));
        self::assertNull($type->asString(-1.0));
    }
}
