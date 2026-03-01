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

use stdClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ConfigResolver
{
    public function __construct(private ParameterBagInterface $params)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->params->has($key) === true) {
            return $this->params->get($key);
        }

        $parts = explode('.', $key, 2);
        $root = array_shift($parts);

        if ($root === null
            || $root === ''
            || is_string($root) === false
            || $this->params->has($root) === false) {
            return $default;
        }

        $value = $this->params->get($root);
        while (count($parts) > 0) {
            $part = array_shift($parts);
            if (is_string($part) === true) {
                if (is_array($value) === true
                    && array_key_exists($part, $value) === true) {
                    return $value[$part];
                }

                $parts = explode('.', $part, 2);
                $part = array_shift($parts);
                if (is_array($value) === true
                    && array_key_exists($part, $value) === true) {
                    $value = $value[$part];
                }
            }
        }

        return $default;
    }

    public function has(string $key): bool
    {
        return $this->get($key, new stdClass()) instanceof stdClass === false;
    }
}
