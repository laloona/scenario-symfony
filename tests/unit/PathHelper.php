<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit;

use function str_replace;

trait PathHelper
{
    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
