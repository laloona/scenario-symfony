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
use Stateforge\Scenario\Symfony\Parameter\PositiveIntegerType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

#[CoversClass(PositiveIntegerType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[Group('parameter')]
#[Small]
final class PositiveIntegerTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new PositiveIntegerType();

        self::assertSame('PositiveIntegerType', $type->name);
        self::assertSame(PositiveIntegerType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForPositiveIntegers(): void
    {
        $type = new PositiveIntegerType();

        self::assertTrue($type->valid(1));
        self::assertFalse($type->valid(0));
        self::assertFalse($type->valid(-1));
        self::assertFalse($type->valid('1'));
    }

    public function testAsStringReturnsIntegerStringAndNullForInvalidInput(): void
    {
        $type = new PositiveIntegerType();

        self::assertSame('1', $type->asString(1));
        self::assertNull($type->asString(0));
        self::assertNull($type->asString('1'));
    }
}
