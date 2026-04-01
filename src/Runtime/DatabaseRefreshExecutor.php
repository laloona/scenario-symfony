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

use Scenario\Core\Attribute\RefreshDatabase;
use Scenario\Core\Contract\DatabaseRefreshExecutorInterface;
use Scenario\Core\Runtime\Application;

final class DatabaseRefreshExecutor implements DatabaseRefreshExecutorInterface
{
    public function __construct(private CommandRunner $commandRunner)
    {
    }

    public function execute(RefreshDatabase $metaData): void
    {
        $parameters = [];
        if ($metaData->connection !== null) {
            $parameters['--connection'] = $metaData->connection;
        }

        $connections = Application::config()?->getConnections() ?? [];
        if (isset($connections[$metaData->connection]) === true) {
            $parameters['--configuration'] = $connections[$metaData->connection]->config;
        }

        $this->commandRunner->execute('scenario:migrations:refresh', $parameters);
    }
}
