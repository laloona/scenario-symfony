<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Parameter;

use Stateforge\Scenario\Core\ParameterTypeCondition;
use function extension_loaded;

final class IntlExtensionCondition extends ParameterTypeCondition
{
    public function matches(): bool
    {
        return extension_loaded('intl');
    }
}
