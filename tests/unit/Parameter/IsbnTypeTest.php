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
use Stateforge\Scenario\Symfony\Parameter\IsbnType;
use Stateforge\Scenario\Symfony\Parameter\StringTypeDefinition;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\Isbn;

#[CoversClass(IsbnType::class)]
#[UsesClass(StringTypeDefinition::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(StringType::class)]
#[UsesClass(Isbn::class)]
#[Group('parameter')]
#[Small]
final class IsbnTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new IsbnType();

        self::assertSame('IsbnType', $type->name);
        self::assertSame(IsbnType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidIsbnValues(): void
    {
        $type = new IsbnType();

        self::assertTrue($type->valid('9783161484100'));
        self::assertFalse($type->valid('invalid-isbn'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsIsbnAndNullForInvalidInput(): void
    {
        $type = new IsbnType();

        self::assertSame('9783161484100', $type->asString('9783161484100'));
        self::assertNull($type->asString('invalid-isbn'));
    }
}
