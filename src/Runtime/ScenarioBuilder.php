<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime;

use Psr\Container\ContainerInterface;
use Stateforge\Scenario\Core\Contract\ScenarioBuilderInterface;
use Stateforge\Scenario\Symfony\Runtime\Exception\ScenarioUnknownException;
use Stateforge\Scenario\Symfony\Runtime\Exception\WrongScenarioSubclassException;
use Stateforge\Scenario\Symfony\Scenario;
use function is_object;
use function is_subclass_of;

final class ScenarioBuilder implements ScenarioBuilderInterface
{
    public function __construct(private ContainerInterface $locator)
    {
    }

    public function build(string $scenarioClass): Scenario
    {
        if ($this->locator->has($scenarioClass) === false) {
            throw new ScenarioUnknownException($scenarioClass);
        }

        $scenario = $this->locator->get($scenarioClass);

        if (is_object($scenario) === true
            && is_subclass_of($scenario, Scenario::class)) {
            return $scenario;
        }

        throw new WrongScenarioSubclassException($scenarioClass);
    }
}
