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
use Stateforge\Scenario\Symfony\Parameter\NegativeOrZeroFloatType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

#[CoversClass(NegativeOrZeroFloatType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[Group('parameter')]
#[Small]
final class NegativeOrZeroFloatTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new NegativeOrZeroFloatType();

        self::assertSame('NegativeOrZeroFloatType', $type->name);
        self::assertSame(NegativeOrZeroFloatType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForNegativeOrZeroFloats(): void
    {
        $type = new NegativeOrZeroFloatType();

        self::assertTrue($type->valid(-1.5));
        self::assertTrue($type->valid(0.0));
        self::assertFalse($type->valid(1.5));
        self::assertTrue($type->valid('-1.5'));
    }

    public function testAsStringReturnsFloatStringAndNullForInvalidInput(): void
    {
        $type = new NegativeOrZeroFloatType();

        self::assertSame('-1.5', $type->asString(-1.5));
        self::assertSame('0', $type->asString(0.0));
        self::assertNull($type->asString(1.5));
        self::assertSame('-1.5', $type->asString('-1.5'));
    }
}
