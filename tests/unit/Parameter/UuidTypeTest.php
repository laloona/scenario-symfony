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
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\StringType;
use Stateforge\Scenario\Symfony\Parameter\StringTypeDefinition;
use Stateforge\Scenario\Symfony\Parameter\UuidType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\Uuid;

#[CoversClass(UuidType::class)]
#[UsesClass(StringTypeDefinition::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(StringType::class)]
#[UsesClass(Uuid::class)]
#[Group('parameter')]
#[Small]
final class UuidTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new UuidType();

        self::assertSame('UuidType', $type->name);
        self::assertSame(UuidType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidUuidValues(): void
    {
        $type = new UuidType();

        self::assertTrue($type->valid('123e4567-e89b-12d3-a456-426614174000'));
        self::assertFalse($type->valid('not-a-uuid'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsUuidAndNullForInvalidInput(): void
    {
        $type = new UuidType();

        self::assertSame('123e4567-e89b-12d3-a456-426614174000', $type->asString('123e4567-e89b-12d3-a456-426614174000'));
        self::assertNull($type->asString('not-a-uuid'));
    }
}
