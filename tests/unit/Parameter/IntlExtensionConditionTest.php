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
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Parameter\IntlExtensionCondition;
use function extension_loaded;

#[CoversClass(IntlExtensionCondition::class)]
#[Group('parameter')]
#[Small]
final class IntlExtensionConditionTest extends TestCase
{
    public function testMatchesReturnsWhetherIntlExtensionIsLoaded(): void
    {
        self::assertSame(
            extension_loaded('intl'),
            (new IntlExtensionCondition())->matches(),
        );
    }
}
