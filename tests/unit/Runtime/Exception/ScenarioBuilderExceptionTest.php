<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Core\Tests\Unit\Runtime\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Scenario\Symfony\Runtime\Exception\ScenarioBuilderException;

#[CoversClass(ScenarioBuilderException::class)]
#[Group('runtime')]
#[Small]
final class ScenarioBuilderExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new ScenarioBuilderException(
            'UnknownScenario',
        );

        self::assertSame(
            'Unknown scenario UnknownScenario',
            $exception->getMessage(),
        );
    }
}
