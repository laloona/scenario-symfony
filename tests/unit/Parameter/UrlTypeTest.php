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
use Stateforge\Scenario\Symfony\Parameter\UrlType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

#[CoversClass(UrlType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[Group('parameter')]
#[Small]
final class UrlTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new UrlType();

        self::assertSame('UrlType', $type->name);
        self::assertSame(UrlType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidUrlValues(): void
    {
        $type = new UrlType();

        self::assertTrue($type->valid('https://example.com'));
        self::assertFalse($type->valid('not-a-url'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsUrlAndNullForInvalidInput(): void
    {
        $type = new UrlType();

        self::assertSame('https://example.com', $type->asString('https://example.com'));
        self::assertNull($type->asString('not-a-url'));
    }
}
