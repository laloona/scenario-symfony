<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Runtime;

use InvalidArgumentException;
use Stateforge\Scenario\Core\Contract\DatabaseRefreshExecutorInterface;
use Stateforge\Scenario\Core\Contract\ScenarioBuilderInterface;
use Stateforge\Scenario\Core\Runtime\Application as CoreApplication;
use Stateforge\Scenario\Core\Runtime\ApplicationExtension;
use Stateforge\Scenario\Core\Runtime\Metadata\Handler\ApplyScenarioHandler;
use Stateforge\Scenario\Core\Runtime\Metadata\Handler\RefreshDatabaseHandler;
use Stateforge\Scenario\Core\Runtime\Metadata\HandlerRegistry;
use Symfony\Component\Dotenv\Dotenv;
use function define;
use function defined;
use function is_string;
use function is_subclass_of;
use const DIRECTORY_SEPARATOR;

final class Application extends ApplicationExtension
{
    public function prepare(): void
    {
        if (defined('SCENARIO_CLI_DISABLED') === false) {
            define('SCENARIO_CLI_DISABLED', true);
        }

        if (CoreApplication::config() === null) {
            return;
        }

        CoreApplication::config()->addParameterDirectory(
            'vendor' . DIRECTORY_SEPARATOR .
            'stateforge' . DIRECTORY_SEPARATOR .
            'scenario-symfony' . DIRECTORY_SEPARATOR .
            'src' . DIRECTORY_SEPARATOR . 'Parameter',
        );
    }

    public function boot(): void
    {
        if (defined('SCENARIO_CLI_DISABLED') === false
            || CoreApplication::config() === null) {
            return;
        }

        (new Dotenv())->bootEnv(CoreApplication::getRootDir() . DIRECTORY_SEPARATOR . '.env');

        $appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        if (is_string($appEnv) === false) {
            throw new InvalidArgumentException('APP_ENV must be a string');
        }

        $kernel = new ScenarioKernel(
            $appEnv,
            (bool) ($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true),
        );
        $kernel->boot();

        /** @var DatabaseRefreshExecutorInterface $refreshDatabaseExecutor */
        $refreshDatabaseExecutor = $kernel->getContainer()->get(DatabaseRefreshExecutorInterface::class);
        if ($refreshDatabaseExecutor !== null) {
            HandlerRegistry::getInstance()->registerHandler(
                new RefreshDatabaseHandler($refreshDatabaseExecutor),
            );
        }

        $scenarioBuilder = $kernel->getContainer()->get('scenario.builder');
        if ($scenarioBuilder instanceof ScenarioBuilderInterface) {
            HandlerRegistry::getInstance()->registerHandler(
                new ApplyScenarioHandler($scenarioBuilder),
            );
        }
    }
}
