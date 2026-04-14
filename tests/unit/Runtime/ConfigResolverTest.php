<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Symfony\Runtime\ConfigResolver;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[CoversClass(ConfigResolver::class)]
#[Group('runtime')]
#[Small]
final class ConfigResolverTest extends TestCase
{
    public function testResolveWithDirectExistingParameterWithoutDot(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->exactly(2))
            ->method('has')
            ->with('parameter')
            ->willReturn(true);
        $parameterBag->expects($this->exactly(2))
            ->method('get')
            ->with('parameter')
            ->willReturn('value');

        $resolver = new ConfigResolver($parameterBag);
        self::assertTrue($resolver->has('parameter'));
        self::assertSame('value', $resolver->get('parameter'));
    }

    public function testResolveWithDirectExistingParameterWithDot(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->exactly(2))
            ->method('has')
            ->with('my.parameter.config')
            ->willReturn(true);
        $parameterBag->expects($this->exactly(2))
            ->method('get')
            ->with('my.parameter.config')
            ->willReturn('value');

        $resolver = new ConfigResolver($parameterBag);
        self::assertTrue($resolver->has('my.parameter.config'));
        self::assertSame('value', $resolver->get('my.parameter.config'));
    }

    public function testResolveNonExistingWithoutDot(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->exactly(6))
            ->method('has')
            ->with('parameter')
            ->willReturn(false);
        $parameterBag->expects($this->never())
            ->method('get')
            ->with('parameter');

        $resolver = new ConfigResolver($parameterBag);
        self::assertFalse($resolver->has('parameter'));
        self::assertNull($resolver->get('parameter'));
        self::assertSame('default', $resolver->get('parameter', 'default'));
    }

    public function testResolveExistingWithDotWith2Parts(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->exactly(4))
            ->method('has')
            ->willReturnOnConsecutiveCalls(false, true, false, true);
        $parameterBag->expects($this->exactly(2))
            ->method('get')
            ->with('my')
            ->willReturn([ 'parameter' => 'value' ]);

        $resolver = new ConfigResolver($parameterBag);
        self::assertTrue($resolver->has('my.parameter'));
        self::assertSame('value', $resolver->get('my.parameter'));
    }

    public function testResolveNonExistingWithDotWith2Parts(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->exactly(6))
            ->method('has')
            ->willReturn(false);
        $parameterBag->expects($this->never())
            ->method('get');

        $resolver = new ConfigResolver($parameterBag);
        self::assertFalse($resolver->has('my.parameter'));
        self::assertNull($resolver->get('my.parameter'));
        self::assertSame('default', $resolver->get('my.parameter', 'default'));
    }

    public function testResolveExistingWithDotWith3Parts(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->exactly(4))
            ->method('has')
            ->willReturnOnConsecutiveCalls(false, true, false, true);
        $parameterBag->expects($this->exactly(2))
            ->method('get')
            ->with('my')
            ->willReturn([ 'parameter' => [ 'config' => 'value' ] ]);

        $resolver = new ConfigResolver($parameterBag);
        self::assertTrue($resolver->has('my.parameter.config'));
        self::assertSame('value', $resolver->get('my.parameter.config'));
    }

    public function testResolveNonExistingWithDotWith3Parts(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->exactly(6))
            ->method('has')
            ->willReturn(false);
        $parameterBag->expects($this->never())
            ->method('get');

        $resolver = new ConfigResolver($parameterBag);
        self::assertFalse($resolver->has('my.parameter.config'));
        self::assertNull($resolver->get('my.parameter.config'));
        self::assertSame('default', $resolver->get('my.parameter.config', 'default'));
    }

    public function testResolvePartlyExistingWithDotWith3Parts(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->exactly(6))
            ->method('has')
            ->willReturnOnConsecutiveCalls(false, true, false, true, false, true);
        $parameterBag->expects($this->exactly(3))
            ->method('get')
            ->with('my')
            ->willReturn([ 'parameter' => [ 'other' => 'value' ] ]);

        $resolver = new ConfigResolver($parameterBag);
        self::assertFalse($resolver->has('my.parameter.config'));
        self::assertNull($resolver->get('my.parameter.config'));
        self::assertSame('default', $resolver->get('my.parameter.config', 'default'));
    }

    public function testResolveExistingPartWithDotWith2Parts(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->exactly(4))
            ->method('has')
            ->willReturnOnConsecutiveCalls(false, true, false, true);
        $parameterBag->expects($this->exactly(2))
            ->method('get')
            ->with('my')
            ->willReturn([ 'parameter' => [ 'other' => 'value' ] ]);

        $resolver = new ConfigResolver($parameterBag);
        self::assertTrue($resolver->has('my.parameter'));
        self::assertSame([ 'other' => 'value' ], $resolver->get('my.parameter'));
    }
}
