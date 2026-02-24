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

use Scenario\Core\Application;
use Scenario\Core\Console\Command\Renderer\ListScenarios;
use Scenario\Symfony\Console\Input;
use Scenario\Symfony\Console\Output;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ScenariosListCommand extends ScenarioCommand
{
    protected function configure(): void
    {
        $this
            ->setName('scenario:list')
            ->setDescription('List all available scenarios, use --suite="name of you suite" if you want to see just one suite. - should only be used for dev/test')
            ->addOption('suite', null, InputOption::VALUE_REQUIRED, 'fiters to given suite')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        new Application()->prepare();
        new ListScenarios()->render(new Input($input), new Output(new SymfonyStyle($input, $output)));
        return Command::SUCCESS;
    }
}
