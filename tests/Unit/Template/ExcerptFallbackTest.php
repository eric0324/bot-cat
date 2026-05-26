<?php
/**
 * @package BotCat\Tests\Unit\Template
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Template;

use BotCat\Template\ExcerptFallback;
use BotCat\Tests\TestCase;

final class ExcerptFallbackTest extends TestCase
{
    public function testAuthoredExcerptIsReturnedAsIs(): void
    {
        $this->assertSame('hand-written summary', ExcerptFallback::derive('hand-written summary', '<p>body</p>'));
    }

    public function testStripsHtmlFromContentFallback(): void
    {
        $this->assertSame('Hello world!', ExcerptFallback::derive('', '<p><strong>Hello</strong> world!</p>'));
    }

    public function testTruncatesAtOneHundredCharsWithEllipsis(): void
    {
        $content  = str_repeat('a', 150);
        $excerpt  = ExcerptFallback::derive('', $content);

        $this->assertSame(str_repeat('a', 100) . '…', $excerpt);
    }

    public function testNoTruncationIfContentExactlyAtLimit(): void
    {
        $content = str_repeat('b', 100);
        $this->assertSame($content, ExcerptFallback::derive('', $content));
    }

    public function testPreservesEmojiAndMultibyteWhileTruncating(): void
    {
        $content = str_repeat('貓', 120); // 120 multibyte chars
        $excerpt = ExcerptFallback::derive('', $content);

        $this->assertSame(str_repeat('貓', 100) . '…', $excerpt);
    }

    public function testEmptyContentProducesEmptyString(): void
    {
        $this->assertSame('', ExcerptFallback::derive('', ''));
    }
}
