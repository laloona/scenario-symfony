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
use Stateforge\Scenario\Symfony\Parameter\DateType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

#[CoversClass(DateType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[Group('parameter')]
#[Small]
final class DateTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new DateType();

        self::assertSame('DateType', $type->name);
        self::assertSame(DateType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidDateValues(): void
    {
        $type = new DateType();

        self::assertTrue($type->valid('2024-01-01'));
        self::assertFalse($type->valid('2024-13-99'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsDateAndNullForInvalidInput(): void
    {
        $type = new DateType();

        self::assertSame('2024-01-01', $type->asString('2024-01-01'));
        self::assertNull($type->asString('2024-13-99'));
    }
}
