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

use Scenario\Core\Console\Command\Command;
use Scenario\Symfony\Runtime\Exception\CommandRunnerException;
use Scenario\Symfony\Runtime\Exception\CommandRunnerResultException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

final class CommandRunner
{
    private static ?Application $application = null;

    public function __construct(
        KernelInterface $kernel,
    ) {
        if (self::$application === null) {
            self::$application = new Application($kernel);
            self::$application->setAutoExit(false);
            self::$application->setCatchExceptions(false);
        }
    }

    /**
     * @param array<string, string> $params
     */
    public function execute(string $command, array $params): void
    {
        try {
            assert(self::$application !== null);

            $input = new ArrayInput($params);
            $input->setInteractive(false);

            $result = self::$application->find($command)->run($input, new NullOutput());
            if ($result !== Command::Success->value) {
                throw new CommandRunnerResultException($command, $result);
            }
        } catch (CommandRunnerResultException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw new CommandRunnerException($command, $throwable);
        }
    }
}
