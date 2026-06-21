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
use Stateforge\Scenario\Symfony\Parameter\PositiveInFloatType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

#[CoversClass(PositiveInFloatType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[Group('parameter')]
#[Small]
final class PositiveInFloatTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new PositiveInFloatType();

        self::assertSame('PositiveInFloatType', $type->name);
        self::assertSame(PositiveInFloatType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForPositiveFloats(): void
    {
        $type = new PositiveInFloatType();

        self::assertTrue($type->valid(1.5));
        self::assertFalse($type->valid(0.0));
        self::assertFalse($type->valid(-1.5));
        self::assertTrue($type->valid('1.5'));
    }

    public function testAsStringReturnsFloatStringAndNullForInvalidInput(): void
    {
        $type = new PositiveInFloatType();

        self::assertSame('1.5', $type->asString(1.5));
        self::assertNull($type->asString(0.0));
        self::assertSame('1.5', $type->asString('1.5'));
    }
}
