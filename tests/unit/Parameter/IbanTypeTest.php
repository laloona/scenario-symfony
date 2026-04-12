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
use Stateforge\Scenario\Symfony\Parameter\IbanType;
use Stateforge\Scenario\Symfony\Parameter\StringTypeDefinition;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\Iban;

#[CoversClass(IbanType::class)]
#[UsesClass(StringTypeDefinition::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(StringType::class)]
#[UsesClass(Iban::class)]
#[Group('parameter')]
#[Small]
final class IbanTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new IbanType();

        self::assertSame('IbanType', $type->name);
        self::assertSame(IbanType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidIbanValues(): void
    {
        $type = new IbanType();

        self::assertTrue($type->valid('DE89370400440532013000'));
        self::assertFalse($type->valid('INVALIDIBAN'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsIbanAndNullForInvalidInput(): void
    {
        $type = new IbanType();

        self::assertSame('DE89370400440532013000', $type->asString('DE89370400440532013000'));
        self::assertNull($type->asString('INVALIDIBAN'));
    }
}
