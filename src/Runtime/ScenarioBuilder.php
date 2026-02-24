<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Runtime;

use Psr\Container\ContainerInterface;
use Scenario\Core\Contract\ScenarioBuilderInterface;
use Scenario\Symfony\Runtime\Exception\ScenarioUnknownException;
use Scenario\Symfony\Runtime\Exception\WrongScenarioSubclassException;
use Scenario\Symfony\Scenario;

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
