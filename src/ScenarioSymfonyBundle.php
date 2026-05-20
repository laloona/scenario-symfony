<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony;

use Stateforge\Scenario\Symfony\Runtime\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ScenarioSymfonyBundle extends Bundle
{
    public function boot(): void
    {
        (new Application())->bootstrap();
    }
}
