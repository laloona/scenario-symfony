<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Runtime\Metadata\Handler;

use Scenario\Core\Attribute\RefreshDatabase;
use Scenario\Core\Runtime\Application;
use Scenario\Core\Runtime\Metadata\AttributeContext;
use Scenario\Core\Runtime\Metadata\ExecutionType;
use Scenario\Core\Runtime\Metadata\Handler\AttributeHandler;
use Scenario\Symfony\Runtime\CommandRunner;

final class RefreshDatabaseHandler extends AttributeHandler
{
    public function __construct(private CommandRunner $commandRunner)
    {
    }

    protected function attributeName(): string
    {
        return RefreshDatabase::class;
    }

    protected function execute(AttributeContext $context, object $metaData): void
    {
        /** @var RefreshDatabase $metaData */
        if ($context->executionType === ExecutionType::Up) {
            $context->audit(__CLASS__, [ 'connection' => $metaData->connection ]);

            if ($context->dryRun === true) {
                return;
            }

            $parameters = [];
            if ($metaData->connection !== null) {
                $parameters['connection'] = $metaData->connection;
            }

            $connections = Application::config()?->getConnections() ?? [];
            if (isset($connections[$metaData->connection]) === true) {
                $parameters['configuration'] = $connections[$metaData->connection]->config;
            }

            $this->commandRunner->execute('scenario:migrations:refresh', $parameters);
        }
    }
}
