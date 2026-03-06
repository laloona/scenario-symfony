<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Command;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

final class RefreshDatabaseCommand extends ScenarioCommand
{
    public function __construct(
        private ManagerRegistry $doctrine,
        KernelInterface $kernel,
        Filesystem $filesystem,
    ) {
        parent::__construct($kernel, $filesystem);
    }

    protected function configure(): void
    {
        $this
            ->setName('scenario:migrations:refresh')
            ->setDescription('Executes the database refresh. Use --connection="connection_name" to specify given connection and --configuration for its configuration - should only be used for dev/test')
            ->addOption('connection', null, InputOption::VALUE_REQUIRED, 'name of connection to use')
            ->addOption('configuration', null, InputOption::VALUE_REQUIRED, 'configuration file for connection to use')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);

        $connectionString = $input->getOption('connection');
        $connectionString = is_string($connectionString) === true
            ? $connectionString
            : $this->doctrine->getDefaultConnectionName();

        try {
            $connection = $this->doctrine->getConnection($connectionString);
            assert($connection instanceof Connection);
        } catch (InvalidArgumentException $exception) {
            if ($input->isInteractive() === true) {
                $style->error('Unknown connection ' . $connectionString);
            }
            return Command::FAILURE;
        }

        if ($input->isInteractive() === true
            && $style->confirm('WARNING! You are about to refresh the database "' . $connection->getDatabase() . '" that could result in schema changes and will result in data loss. Are you sure you wish to continue? (yes/no)?', false) === false) {
            $style->error('Refresh cancelled!');
            return Command::SUCCESS;
        }

        $app = $this->getApplication();
        if ($app === null) {
            if ($input->isInteractive() === true) {
                $style->error('No console application available.');
            }
            return Command::FAILURE;
        }

        $commands = [
            'doctrine:database:drop' => ['--force' => true, '--if-exists' => true],
            'doctrine:database:create' => [],
            'doctrine:migrations:migrate' => [],
        ];

        if ($input->getOption('connection') !== null
            && is_string($input->getOption('connection')) === true) {
            $commands['doctrine:database:drop']['--connection'] = $input->getOption('connection');
            $commands['doctrine:database:create']['--connection'] = $input->getOption('connection');
            $commands['doctrine:migrations:migrate']['--conn'] = $input->getOption('connection');
        }

        if ($input->getOption('configuration') !== null
            && is_string($input->getOption('configuration')) === true) {
            $commands['doctrine:migrations:migrate']['--configuration'] = $input->getOption('configuration');
        }

        foreach ($commands as $command => $args) {
            if ($input->isInteractive() === true) {
                $style->info('Execute ' . $command . ' ' . implode(' ', array_keys($args)));
            }

            try {
                $commandArgs = new ArrayInput($args);
                $commandArgs->setInteractive(false);

                $result = $app->find($command)->run($commandArgs, ($input->isInteractive() === true) ? $output : new NullOutput());
                if ($result !== Command::SUCCESS) {
                    if ($input->isInteractive() === true) {
                        $style->error('Refresh failed!');
                    }
                    return Command::FAILURE;
                }
            } catch (Throwable $throwable) {
                if ($input->isInteractive() === true) {
                    $style->error('Command ' . $command . ' failed: ' . $throwable->getMessage());
                }
            }
        }

        return Command::SUCCESS;
    }
}
