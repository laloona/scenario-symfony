<?php declare(strict_types=1);

/*
 * This file is part of Scenario\Symfony package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scenario\Symfony\Tests\Unit\Console;

use Scenario\Symfony\Console\Output;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

final class OutputTest extends KernelTestCase
{
    public function testConfirmDelegatesToSymfonyStyle(): void
    {
        $style = self::createMock(SymfonyStyle::class);
        $style->expects(self::once())
            ->method('confirm')
            ->with('Are you sure?', true)
            ->willReturn(false);

        self::assertFalse(new Output($style)->confirm('Are you sure?', true));
    }

    public function testHeadlineUsesSection(): void
    {
        $style = self::createMock(SymfonyStyle::class);
        $style->expects(self::once())
            ->method('section')
            ->with('Hello');

        new Output($style)->headline('Hello');
    }

    public function testSuccessDelegates(): void
    {
        $style = self::createMock(SymfonyStyle::class);
        $style->expects(self::once())
            ->method('success')
            ->with('OK');

        new Output($style)->success('OK');
    }

    public function testErrorDelegates(): void
    {
        $style = self::createMock(SymfonyStyle::class);
        $style->expects(self::once())
            ->method('error')
            ->with('Nope');

        new Output($style)->error('Nope');
    }

    public function testQuestionUsesBlockAndNewLine(): void
    {
        $style = self::createMock(SymfonyStyle::class);

        $style->expects(self::once())
            ->method('block')
            ->with('What now?', null, 'fg=white;bg=bright-blue', ' ', true);

        $style->expects(self::once())
            ->method('newLine');

        new Output($style)->question('What now?');
    }

    public function testChoiceAsksQuestionThenChoiceThenNewLine(): void
    {
        $style = self::createMock(SymfonyStyle::class);

        // question() calls: block + newLine
        $style->expects(self::once())
            ->method('block')
            ->with('Pick one', null, 'fg=white;bg=bright-blue', ' ', true);

        // choice() calls: choice + newLine after
        $style->expects(self::once())
            ->method('choice')
            ->with('Please select one of the following:', ['a', 'b'], 'b')
            ->willReturn('a');

        // total newLine calls: one from question(), one after choice()
        $style->expects(self::exactly(2))
            ->method('newLine');

        $output = new Output($style);
        self::assertSame('a', $output->choice('Pick one', ['a', 'b'], 'b'));
    }

    public function testAskReturnsAnswerWhenValid(): void
    {
        $style = self::createMock(SymfonyStyle::class);

        $style->expects(self::once())
            ->method('ask')
            ->with('Name?', 'x', null)
            ->willReturn('Christina');

        $output = new Output($style);
        self::assertSame('Christina', $output->ask('Name?', 'x'));
    }

    public function xtestAskRepeatsWhenAnswerIsFalse(): void
    {
        /*$style = self::createMock(SymfonyStyle::class);

        $style->expects($this->exactly(2))
            ->method('ask')
            ->withConsecutive(
                ['Name?', null, null],
                ['<error>Input was invalid, please try again</error>', null, null],
            )
            ->willReturnOnConsecutiveCalls(false, 'OK');

        $output = new Output($style);
        self::assertSame('OK', $output->ask('Name?'));*/
    }

    public function testTableSetsHeadersRowsAlignBorderAndRenders(): void
    {
        $style = self::createMock(SymfonyStyle::class);
        $table = self::createMock(Table::class);

        $style->expects(self::once())
            ->method('createTable')
            ->willReturn($table);

        $table->expects(self::once())
            ->method('setHeaders')
            ->with(['H1', 'H2'])
            ->willReturnSelf();

        $table->expects(self::once())
            ->method('setRows')
            ->with([['a', 'b']])
            ->willReturnSelf();

        // alignment: verify setColumnStyle called for each provided column with a TableStyle instance
        $table->expects(self::exactly(2))
            ->method('setColumnStyle')
            ->with(
                self::logicalOr(self::equalTo(0), self::equalTo(1)),
                self::callback(function ($arg): bool {
                    return $arg instanceof TableStyle;
                }),
            )
            ->willReturnSelf();

        // showBorder=true: must setStyle(TableStyle)
        $table->expects(self::once())
            ->method('setStyle')
            ->with(self::isInstanceOf(TableStyle::class))
            ->willReturnSelf();

        $table->expects(self::once())
            ->method('render');

        $style->expects(self::once())
            ->method('newLine');

        $output = new Output($style);

        $output->table(
            headers: ['H1', 'H2'],
            rows: [['a', 'b']],
            align: [0 => 'left', 1 => 'center'],
            showBorder: true,
        );
    }

    public function testTableWithoutHeadersDoesNotCallSetHeaders(): void
    {
        $style = self::createMock(SymfonyStyle::class);
        $table = self::createMock(Table::class);

        $style->expects(self::once())
            ->method('createTable')
            ->willReturn($table);

        $table->expects(self::never())
            ->method('setHeaders');

        $table->expects(self::once())
            ->method('setRows')
            ->with([['x']])
            ->willReturnSelf();

        $table->expects(self::once())
            ->method('setStyle')
            ->with(self::isInstanceOf(TableStyle::class))
            ->willReturnSelf();

        $table->expects(self::once())->method('render');

        $style->expects(self::once())->method('newLine');

        (new Output($style))->table(null, [['x']]);
    }

    public function testTableBorderlessUsesBorderlessStyleString(): void
    {
        $style = self::createMock(SymfonyStyle::class);
        $table = self::createMock(Table::class);

        $style->expects(self::once())
            ->method('createTable')
            ->willReturn($table);

        $table->expects(self::once())
            ->method('setRows')
            ->with([['x']])
            ->willReturnSelf();

        $table->expects(self::once())
            ->method('setStyle')
            ->with('borderless')
            ->willReturnSelf();

        $table->expects(self::once())->method('render');

        $style->expects(self::once())->method('newLine');

        (new Output($style))->table(null, [['x']], null, false);
    }
}
