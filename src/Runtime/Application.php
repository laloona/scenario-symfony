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

use InvalidArgumentException;
use Scenario\Core\Application as CoreApplication;
use Scenario\Core\Contract\ScenarioBuilderInterface;
use Scenario\Core\Runtime\Metadata\Handler\ApplyScenarioHandler;
use Scenario\Core\Runtime\Metadata\Handler\AttributeHandler;
use Scenario\Core\Runtime\Metadata\HandlerRegistry;
use Symfony\Component\Dotenv\Dotenv;
use function define;
use function defined;
use function is_string;
use function is_subclass_of;

final class Application
{
    public function bootstrap(): void
    {
        if (defined('SCENARIO_CLI_DISABLED') === false) {
            define('SCENARIO_CLI_DISABLED', true);
        }

        // core kernel is not prepared, this file was loaded by file scan
        if (CoreApplication::config() === null) {
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

        $refreshDatabaseHandler = $kernel->getContainer()->get('scenario.refresh_database_handler');
        if ($refreshDatabaseHandler !== null
            && is_subclass_of($refreshDatabaseHandler, AttributeHandler::class) === true) {
            HandlerRegistry::getInstance()->registerHandler($refreshDatabaseHandler);
        }

        $scenarioBuilder = $kernel->getContainer()->get('scenario.builder');
        if ($scenarioBuilder !== null
            && is_subclass_of($scenarioBuilder, ScenarioBuilderInterface::class) === true) {
            HandlerRegistry::getInstance()->registerHandler(new ApplyScenarioHandler($scenarioBuilder));
        }
    }
}
