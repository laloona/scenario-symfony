<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Files;

use Stateforge\Scenario\Symfony\Command\ScenarioCommand;

final class TestScenarioCommand extends ScenarioCommand
{
    public function isAllowedPublic(): bool
    {
        return $this->isAllowed();
    }

    public function getBlueprintPublic(string $blueprintFile): string
    {
        return $this->getBlueprint($blueprintFile);
    }

    public function getCliPathPublic(): string
    {
        return $this->getCliPath();
    }
}
