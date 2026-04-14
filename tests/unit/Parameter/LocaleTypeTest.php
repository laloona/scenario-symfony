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
use Stateforge\Scenario\Symfony\Parameter\IntlExtensionCondition;
use Stateforge\Scenario\Symfony\Parameter\LocaleType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

#[CoversClass(LocaleType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[Group('parameter')]
#[Small]
final class LocaleTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new LocaleType();

        self::assertSame('LocaleType', $type->name);
        self::assertSame(LocaleType::class, $type->value);
    }

    public function testHasIntlExtensionConditionAttribute(): void
    {
        $attributes = (new ReflectionClass(LocaleType::class))->getAttributes(ParameterTypeCondition::class);

        self::assertCount(1, $attributes);
        self::assertSame(IntlExtensionCondition::class, $attributes[0]->newInstance()->condition);
    }

    #[RequiresPhpExtension('intl')]
    public function testValidReturnsTrueOnlyForValidLocaleValues(): void
    {
        $type = new LocaleType();

        self::assertTrue($type->valid('en_US'));
        self::assertFalse($type->valid('invalid_locale'));
        self::assertFalse($type->valid(null));
    }

    #[RequiresPhpExtension('intl')]
    public function testAsStringReturnsLocaleAndNullForInvalidInput(): void
    {
        $type = new LocaleType();

        self::assertSame('en_US', $type->asString('en_US'));
        self::assertNull($type->asString('invalid_locale'));
    }
}
