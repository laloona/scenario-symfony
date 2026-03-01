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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Scenario\Symfony\Console\Output;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

#[CoversClass(Output::class)]
#[Group('console')]
final class OutputTest extends KernelTestCase
{
    public function testConfirmDelegatesToSymfonyStyle(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('confirm')
            ->with('Are you sure?', true)
            ->willReturn(false);

        self::assertFalse(new Output($style)->confirm('Are you sure?', true));
    }

    public function testHeadlineUsesSection(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('section')
            ->with('Hello');

        new Output($style)->headline('Hello');
    }

    public function testSuccessDelegates(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('success')
            ->with('OK');

        new Output($style)->success('OK');
    }

    public function testErrorDelegates(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('error')
            ->with('Nope');

        new Output($style)->error('Nope');
    }

    public function testQuestionUsesBlockAndNewLine(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('block')
            ->with('What now?', null, 'fg=white;bg=bright-blue', ' ', true);
        $style->expects($this->once())
            ->method('newLine');

        new Output($style)->question('What now?');
    }

    public function testChoiceAsksQuestionThenChoiceThenNewLine(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        // question() calls: block + newLine
        $style->expects($this->once())
            ->method('block')
            ->with('Pick one', null, 'fg=white;bg=bright-blue', ' ', true);

        // choice() calls: choice + newLine after
        $style->expects($this->once())
            ->method('choice')
            ->with('Please select one of the following:', ['a', 'b'], 'b')
            ->willReturn('a');

        // total newLine calls: one from question(), one after choice()
        $style->expects($this->exactly(2))
            ->method('newLine');

        $output = new Output($style);
        self::assertSame('a', $output->choice('Pick one', ['a', 'b'], 'b'));
    }

    public function testAskReturnsAnswerWhenValid(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('ask')
            ->with('Name?', 'x', null)
            ->willReturn('Christina');

        $output = new Output($style);
        self::assertSame('Christina', $output->ask('Name?', 'x'));
    }

    public function xtestAskRepeatsWhenAnswerIsFalse(): void
    {
        /*$style = $this->createMock(SymfonyStyle::class);

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

        // alignment: verify setColumnStyle called for each provided column with a TableStyle instance
        $table->expects($this->exactly(2))
            ->method('setColumnStyle')
            ->with(
                self::logicalOr(self::equalTo(0), self::equalTo(1)),
                self::callback(function ($arg): bool {
                    return $arg instanceof TableStyle;
                }),
            )
            ->willReturnSelf();

        // showBorder=true: must setStyle(TableStyle)
        $table->expects($this->once())
            ->method('setStyle')
            ->with(self::isInstanceOf(TableStyle::class))
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('render');

        $style->expects($this->once())
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
        $style = $this->createMock(SymfonyStyle::class);
        $table = $this->createMock(Table::class);

        $style->expects($this->once())
            ->method('createTable')
            ->willReturn($table);

        $table->expects(self::never())
            ->method('setHeaders');

        $table->expects($this->once())
            ->method('setRows')
            ->with([['x']])
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setStyle')
            ->with(self::isInstanceOf(TableStyle::class))
            ->willReturnSelf();

        $table->expects($this->once())->method('render');

        $style->expects($this->once())->method('newLine');

        (new Output($style))->table(null, [['x']]);
    }

    public function testTableBorderlessUsesBorderlessStyleString(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $table = $this->createMock(Table::class);

        $style->expects($this->once())
            ->method('createTable')
            ->willReturn($table);

        $table->expects($this->once())
            ->method('setRows')
            ->with([['x']])
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setStyle')
            ->with('borderless')
            ->willReturnSelf();

        $table->expects($this->once())->method('render');

        $style->expects($this->once())->method('newLine');

        (new Output($style))->table(null, [['x']], null, false);
    }
}
