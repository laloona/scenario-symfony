<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Console;

use Scenario\Core\Contract\CliInput;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;

final class Input implements CliInput
{
    public function __construct(private InputInterface $input)
    {
    }

    public function command(): ?string
    {
        return $this->input->getFirstArgument();
    }

    public function argument(string $name): null|bool|string
    {
        try {
            $value = $this->input->getArgument($name);
            if ($value === null || is_bool($value) || is_string($value)) {
                return $value;
            }
        } catch (InvalidArgumentException $exception) {
        }

        return null;
    }

    public function option(string $name): null|bool|string
    {
        try {
            $value = $this->input->getOption($name);
            if ($value === null || is_bool($value) || is_string($value)) {
                return $value;
            }
        } catch (InvalidArgumentException $exception) {
        }

        return null;
    }
}
