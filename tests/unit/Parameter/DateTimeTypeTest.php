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
use Stateforge\Scenario\Symfony\Parameter\DateTimeType;
use Stateforge\Scenario\Symfony\Parameter\StringTypeDefinition;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\DateTime;

#[CoversClass(DateTimeType::class)]
#[UsesClass(StringTypeDefinition::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(StringType::class)]
#[UsesClass(DateTime::class)]
#[Group('parameter')]
#[Small]
final class DateTimeTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new DateTimeType();

        self::assertSame('DateTimeType', $type->name);
        self::assertSame(DateTimeType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidDateTimeValues(): void
    {
        $type = new DateTimeType();

        self::assertTrue($type->valid('2024-01-01 12:34:56'));
        self::assertFalse($type->valid('not-a-date-time'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsDateTimeAndNullForInvalidInput(): void
    {
        $type = new DateTimeType();

        self::assertSame('2024-01-01 12:34:56', $type->asString('2024-01-01 12:34:56'));
        self::assertNull($type->asString('not-a-date-time'));
    }
}
