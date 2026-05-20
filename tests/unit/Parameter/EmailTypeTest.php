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
use Stateforge\Scenario\Symfony\Parameter\EmailType;
use Stateforge\Scenario\Symfony\ParameterTypeDefinition;

#[CoversClass(EmailType::class)]
#[UsesClass(ParameterTypeDefinition::class)]
#[Group('parameter')]
#[Small]
final class EmailTypeTest extends TestCase
{
    public function testConstructSetsNameAndValueFromDefinition(): void
    {
        $type = new EmailType();

        self::assertSame('EmailType', $type->name);
        self::assertSame(EmailType::class, $type->value);
    }

    public function testValidReturnsTrueOnlyForValidEmailValues(): void
    {
        $type = new EmailType();

        self::assertTrue($type->valid('test@example.com'));
        self::assertFalse($type->valid('not-an-email'));
        self::assertFalse($type->valid(null));
    }

    public function testAsStringReturnsEmailAndNullForInvalidInput(): void
    {
        $type = new EmailType();

        self::assertSame('user@example.com', $type->asString('user@example.com'));
        self::assertNull($type->asString('invalid-email'));
    }
}
