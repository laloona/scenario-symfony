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
use Stateforge\Scenario\Symfony\Parameter\HostnameType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;
use Symfony\Component\Validator\Constraints\Hostname;

#[CoversClass(HostnameType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[UsesClass(StringType::class)]
#[UsesClass(Hostname::class)]
#[Group('parameter')]
#[Small]
final class HostnameTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new HostnameType();

        self::assertSame('HostnameType', $type->name);
        self::assertSame(HostnameType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidHostnameValues(): void
    {
        $type = new HostnameType();

        self::assertTrue($type->valid('example.com'));
        self::assertFalse($type->valid('invalid host name'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsHostnameAndNullForInvalidInput(): void
    {
        $type = new HostnameType();

        self::assertSame('example.com', $type->asString('example.com'));
        self::assertNull($type->asString('invalid host name'));
    }
}
