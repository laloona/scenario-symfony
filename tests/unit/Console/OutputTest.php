<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Symfony\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use Stateforge\Scenario\Core\Console\Output\Formatter\Align;
use Stateforge\Scenario\Symfony\Console\Output;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

#[CoversClass(Output::class)]
#[Group('console')]
#[Small]
final class OutputTest extends KernelTestCase
{
    public function testConfirmDelegatesToSymfonyStyle(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('confirm')
            ->with('Are you sure?', true)
            ->willReturn(false);

        self::assertFalse((new Output($style))->confirm('Are you sure?', true));
    }

    public function testHeadlineUsesSection(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('section')
            ->with('Hello');

        (new Output($style))->headline('Hello');
    }

    public function testSuccessDelegates(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('success')
            ->with('OK');

        (new Output($style))->success('OK');
    }

    public function testErrorDelegates(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('error')
            ->with('Error');

        (new Output($style))->error('Error');
    }

    public function testWarnDelegates(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('warning')
            ->with('Warn');

        (new Output($style))->warn('Warn');
    }

    public function testQuestionUsesBlockAndNewLine(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(2))
            ->method('newLine');
        $style->expects($this->once())
            ->method('block')
            ->with('My Question?', null, 'fg=white;bg=blue', ' ', true);

        (new Output($style))->question('My Question?');
    }

    public function testChoiceAsksQuestionThenChoiceThenNewLine(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(3))
            ->method('newLine');

        $style->expects($this->once())
            ->method('block')
            ->with('Select one', null, 'fg=white;bg=blue', ' ', true);

        $style->expects($this->once())
            ->method('choice')
            ->with('Please select one of the following:', ['a', 'b'], 'b')
            ->willReturn('a');

        self::assertSame('a', (new Output($style))->choice('Select one', ['a', 'b'], 'b'));
    }

    public function testChoiceReturnsEmptyStringForNonStringAnswer(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(3))
            ->method('newLine');

        $style->expects($this->once())
            ->method('block')
            ->with('Select one', null, 'fg=white;bg=blue', ' ', true);

        $style->expects($this->once())
            ->method('choice')
            ->with('Please select one of the following:', ['a', 'b'], null)
            ->willReturn(['a']);

        self::assertSame('', (new Output($style))->choice('Select one', ['a', 'b']));
    }

    public function testAskReturnsAnswerWhenValid(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('ask')
            ->with('Name?', 'OtherName', null)
            ->willReturn('MyName');

        self::assertSame('MyName', (new Output($style))->ask('Name?', 'OtherName'));
    }

    public function testAskRepeatsWhenAnswerIsFalse(): void
    {
        $style = $this->createMock(SymfonyStyle::class);

        // todo: with phpunit 13 use withParameterSetsInOrder
        $matcher = $this->exactly(2);
        $style->expects($matcher)
            ->method('ask')
            ->willReturnCallback(function (string $question, ?string $default, ?callable $validator) use ($matcher) {
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        self::assertSame('Name?', $question);
                        self::assertNull($default);
                        self::assertNull($validator);
                        return false;
                    case 2:
                        self::assertSame('<error>Input was invalid, please try again</error>', $question);
                        self::assertNull($default);
                        self::assertNull($validator);
                        return 'OK';
                }
            });

        $output = new Output($style);
        self::assertSame('OK', $output->ask('Name?'));
    }

    public function testAskReturnsNullForNonScalarAnswer(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('ask')
            ->with('Name?', null, null)
            ->willReturn(['x']);

        self::assertNull((new Output($style))->ask('Name?'));
    }

    public function testTableSetsHeadersRowsAlignBorderAndRenders(): void
    {
        $table = $this->createMock(Table::class);

        $table->expects($this->once())
            ->method('setHeaders')
            ->with(['H1', 'H2'])
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setRows')
            ->with([['a', 'b']])
            ->willReturnSelf();

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('createTable')
            ->willReturn($table);

        $table->expects($this->exactly(2))
            ->method('setColumnStyle')
            ->with(
                self::logicalOr(self::equalTo(0), self::equalTo(1)),
                self::callback(function ($arg): bool {
                    return $arg instanceof TableStyle;
                }),
            )
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setStyle')
            ->with(self::isInstanceOf(TableStyle::class))
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('render');

        $style->expects($this->exactly(2))
            ->method('newLine');

        (new Output($style))->table(
            headers: ['H1', 'H2'],
            rows: [['a', 'b']],
            align: [0 => Align::Left, 1 => Align::Center],
            showBorder: true,
        );
    }

    public function testTableAlignRightAndCenterUsesTableStyle(): void
    {
        $table = $this->createMock(Table::class);
        $table->expects($this->once())
            ->method('setRows')
            ->with([['a', 'b']])
            ->willReturnSelf();

        $table->expects($this->exactly(2))
            ->method('setColumnStyle')
            ->with(
                self::logicalOr(self::equalTo(0), self::equalTo(1)),
                self::callback(function ($arg): bool {
                    return $arg instanceof TableStyle;
                }),
            )
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setStyle')
            ->with(self::isInstanceOf(TableStyle::class))
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('render');

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(2))
            ->method('newLine');
        $style->expects($this->once())
            ->method('createTable')
            ->willReturn($table);

        (new Output($style))->table(
            headers: null,
            rows: [['a', 'b']],
            align: [0 => Align::Right, 1 => Align::Center],
            showBorder: true,
        );
    }

    public function testTableWithoutHeadersDoesNotCallSetHeaders(): void
    {
        $table = $this->createMock(Table::class);
        $table->expects(self::never())
            ->method('setHeaders');

        $table->expects($this->once())
            ->method('setRows')
            ->with([['cell']])
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setStyle')
            ->with(self::isInstanceOf(TableStyle::class))
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('render');

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(2))
            ->method('newLine');

        $style->expects($this->once())
            ->method('createTable')
            ->willReturn($table);

        (new Output($style))->table(null, [['cell']]);
    }

    public function testTableBorderlessUsesBorderlessStyleString(): void
    {
        $table = $this->createMock(Table::class);
        $table->expects($this->once())
            ->method('setRows')
            ->with([['cell']])
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setStyle')
            ->with('borderless')
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('render');

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(2))
            ->method('newLine');

        $style->expects($this->once())
            ->method('createTable')
            ->willReturn($table);

        (new Output($style))->table(null, [['cell']], null, false);
    }

    public function testWritelnWritesLinesForStringAndArray(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(3))
            ->method('writeln')
            ->with(self::logicalOr('one', 'two', 'three'));

        $output = new Output($style);
        $output->writeln('one');
        $output->writeln(['two', 'three']);
    }
}
