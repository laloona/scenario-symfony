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
use Stateforge\Scenario\Symfony\Parameter\IpType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\Ip;

#[CoversClass(IpType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(StringType::class)]
#[UsesClass(Ip::class)]
#[Group('parameter')]
#[Small]
final class IpTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new IpType();

        self::assertSame('IpType', $type->name);
        self::assertSame(IpType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidIpValues(): void
    {
        $type = new IpType();

        self::assertTrue($type->valid('127.0.0.1'));
        self::assertFalse($type->valid('999.999.999.999'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsIpAndNullForInvalidInput(): void
    {
        $type = new IpType();

        self::assertSame('127.0.0.1', $type->asString('127.0.0.1'));
        self::assertNull($type->asString('invalid-ip'));
    }
}
