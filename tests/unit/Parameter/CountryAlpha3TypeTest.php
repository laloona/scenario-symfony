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
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Core\Attribute\ParameterTypeCondition;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\StringType;
use Stateforge\Scenario\Symfony\Parameter\CountryAlpha3Type;
use Stateforge\Scenario\Symfony\Parameter\IntlExtensionCondition;
use Stateforge\Scenario\Symfony\Parameter\StringTypeDefinition;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\Country;

#[CoversClass(CountryAlpha3Type::class)]
#[UsesClass(StringTypeDefinition::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(StringType::class)]
#[UsesClass(Country::class)]
#[Group('parameter')]
#[Small]
final class CountryAlpha3TypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new CountryAlpha3Type();

        self::assertSame('CountryAlpha3Type', $type->name);
        self::assertSame(CountryAlpha3Type::class, $type->value);
    }

    public function testHasIntlExtensionConditionAttribute(): void
    {
        $attributes = (new ReflectionClass(CountryAlpha3Type::class))->getAttributes(ParameterTypeCondition::class);

        self::assertCount(1, $attributes);
        self::assertSame(IntlExtensionCondition::class, $attributes[0]->newInstance()->condition);
    }

    #[RequiresPhpExtension('intl')]
    public function testValidReturnsTrueOnlyForValidAlpha3CountryValues(): void
    {
        $type = new CountryAlpha3Type();

        self::assertTrue($type->valid('DEU'));
        self::assertFalse($type->valid('DE'));
        self::assertFalse($type->valid(null));
    }

    #[RequiresPhpExtension('intl')]
    public function testAsStringReturnsCountryAndNullForInvalidInput(): void
    {
        $type = new CountryAlpha3Type();

        self::assertSame('DEU', $type->asString('DEU'));
        self::assertNull($type->asString('DE'));
    }
}
