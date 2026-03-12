<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Tests\Files;

use Symfony\Component\Console\Command\Command;
use Throwable;

final class DemoCommand extends Command
{
    public function __construct(
        private readonly int $result,
        private readonly ?Throwable $throwable = null,
    ) {
        parent::__construct('test');
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        if ($this->throwable !== null) {
            throw $this->throwable;
        }

        return $this->result;
    }
}
