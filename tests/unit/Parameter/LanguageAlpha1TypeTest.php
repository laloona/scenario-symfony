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
use Stateforge\Scenario\Symfony\Parameter\LanguageAlpha1Type;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

#[CoversClass(LanguageAlpha1Type::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[Group('parameter')]
#[Small]
final class LanguageAlpha1TypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new LanguageAlpha1Type();

        self::assertSame('LanguageAlpha1Type', $type->name);
        self::assertSame(LanguageAlpha1Type::class, $type->value);
    }

    public function testHasIntlExtensionConditionAttribute(): void
    {
        $attributes = (new ReflectionClass(LanguageAlpha1Type::class))->getAttributes(ParameterTypeCondition::class);

        self::assertCount(1, $attributes);
        self::assertSame(IntlExtensionCondition::class, $attributes[0]->newInstance()->condition);
    }

    #[RequiresPhpExtension('intl')]
    public function testValidReturnsTrueOnlyForValidAlpha1LanguageValues(): void
    {
        $type = new LanguageAlpha1Type();

        self::assertTrue($type->valid('en'));
        self::assertFalse($type->valid('english'));
        self::assertFalse($type->valid(null));
    }

    #[RequiresPhpExtension('intl')]
    public function testAsStringReturnsLanguageAndNullForInvalidInput(): void
    {
        $type = new LanguageAlpha1Type();

        self::assertSame('en', $type->asString('en'));
        self::assertNull($type->asString('english'));
    }
}
