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
use Stateforge\Scenario\Symfony\Parameter\TimeType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\Time;

#[CoversClass(TimeType::class)]
#[UsesClass(StringTypeDefinition::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(StringType::class)]
#[UsesClass(Time::class)]
#[Group('parameter')]
#[Small]
final class TimeTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new TimeType();

        self::assertSame('TimeType', $type->name);
        self::assertSame(TimeType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidTimeValues(): void
    {
        $type = new TimeType();

        self::assertTrue($type->valid('13:37:00'));
        self::assertFalse($type->valid('25:99:99'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsTimeAndNullForInvalidInput(): void
    {
        $type = new TimeType();

        self::assertSame('13:37:00', $type->asString('13:37:00'));
        self::assertNull($type->asString('25:99:99'));
    }
}
