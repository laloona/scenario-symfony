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
use Stateforge\Scenario\Symfony\Parameter\BicType;
use Stateforge\Scenario\Symfony\Parameter\IntlExtensionCondition;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

#[CoversClass(BicType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[Group('parameter')]
#[Small]
final class BicTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new BicType();

        self::assertSame('BicType', $type->name);
        self::assertSame(BicType::class, $type->value);
    }

    public function testHasIntlExtensionConditionAttribute(): void
    {
        $attributes = (new ReflectionClass(BicType::class))->getAttributes(ParameterTypeCondition::class);

        self::assertCount(1, $attributes);
        self::assertSame(IntlExtensionCondition::class, $attributes[0]->newInstance()->condition);
    }

    #[RequiresPhpExtension('intl')]
    public function testValidReturnsTrueOnlyForValidBicValues(): void
    {
        $type = new BicType();

        self::assertTrue($type->valid('DEUTDEFF'));
        self::assertFalse($type->valid('invalid-bic'));
        self::assertFalse($type->valid(null));
    }

    #[RequiresPhpExtension('intl')]
    public function testAsStringReturnsBicAndNullForInvalidInput(): void
    {
        $type = new BicType();

        self::assertSame('DEUTDEFF', $type->asString('DEUTDEFF'));
        self::assertNull($type->asString('invalid-bic'));
    }
}
