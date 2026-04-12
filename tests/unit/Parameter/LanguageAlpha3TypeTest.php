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
use Stateforge\Scenario\Symfony\Parameter\IntlExtensionCondition;
use Stateforge\Scenario\Symfony\Parameter\LanguageAlpha3Type;
use Stateforge\Scenario\Symfony\Parameter\StringTypeDefinition;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\Language;

#[CoversClass(LanguageAlpha3Type::class)]
#[UsesClass(StringTypeDefinition::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(StringType::class)]
#[UsesClass(Language::class)]
#[Group('parameter')]
#[Small]
final class LanguageAlpha3TypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new LanguageAlpha3Type();

        self::assertSame('LanguageAlpha3Type', $type->name);
        self::assertSame(LanguageAlpha3Type::class, $type->value);
    }

    public function testHasIntlExtensionConditionAttribute(): void
    {
        $attributes = (new ReflectionClass(LanguageAlpha3Type::class))->getAttributes(ParameterTypeCondition::class);

        self::assertCount(1, $attributes);
        self::assertSame(IntlExtensionCondition::class, $attributes[0]->newInstance()->condition);
    }

    #[RequiresPhpExtension('intl')]
    public function testValidReturnsTrueOnlyForValidAlpha3LanguageValues(): void
    {
        $type = new LanguageAlpha3Type();

        self::assertTrue($type->valid('eng'));
        self::assertFalse($type->valid('english'));
        self::assertFalse($type->valid(null));
    }

    #[RequiresPhpExtension('intl')]
    public function testAsStringReturnsLanguageAndNullForInvalidInput(): void
    {
        $type = new LanguageAlpha3Type();

        self::assertSame('eng', $type->asString('eng'));
        self::assertNull($type->asString('english'));
    }
}
