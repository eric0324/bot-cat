<?php
/**
 * @package BotCat\Tests\Unit\Shortener
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Shortener;

use BotCat\Shortener\LinkRewriter;
use BotCat\Shortener\ShortLink;
use BotCat\Shortener\ShortLinkRepository;
use BotCat\Tests\TestCase;

final class LinkRewriterTest extends TestCase
{
    public function testReplacesSameSitePermalinkWithShortUrl(): void
    {
        $links = $this->createMock(ShortLinkRepository::class);
        $links->method('create_for_post')
            ->with(42, 'https://x.test/2026/05/foo')
            ->willReturn(new ShortLink(1, 'aB3xY9', 42, 'https://x.test/2026/05/foo', ''));

        $rewriter = new LinkRewriter($links, 'https://x.test', 'https://x.test/l/');
        $result   = $rewriter->rewrite(
            'Title • https://x.test/2026/05/foo',
            42,
            'https://x.test/2026/05/foo'
        );

        $this->assertSame('Title • https://x.test/l/aB3xY9', $result);
    }

    public function testRewritesMultipleOccurrencesOfPermalink(): void
    {
        $links = $this->createMock(ShortLinkRepository::class);
        $links->method('create_for_post')->willReturn(new ShortLink(1, 'aB3xY9', 42, 'https://x.test/2026/05/foo', ''));

        $rewriter = new LinkRewriter($links, 'https://x.test', 'https://x.test/l/');
        $result   = $rewriter->rewrite(
            'See https://x.test/2026/05/foo or https://x.test/2026/05/foo again.',
            42,
            'https://x.test/2026/05/foo'
        );

        $this->assertStringNotContainsString('2026/05/foo', $result);
        $this->assertEquals(2, substr_count($result, 'https://x.test/l/aB3xY9'));
    }

    public function testExternalUrlsAreLeftAlone(): void
    {
        $links = $this->createMock(ShortLinkRepository::class);
        $links->method('create_for_post')->willReturn(new ShortLink(1, 'aB3xY9', 42, 'https://x.test/post/42', ''));

        $rewriter = new LinkRewriter($links, 'https://x.test', 'https://x.test/l/');
        $result   = $rewriter->rewrite(
            'See https://other-site.test/article and https://x.test/post/42',
            42,
            'https://x.test/post/42'
        );

        $this->assertStringContainsString('https://other-site.test/article', $result);
        $this->assertStringContainsString('https://x.test/l/aB3xY9', $result);
    }

    public function testEmptyPermalinkIsNoOp(): void
    {
        $links = $this->createMock(ShortLinkRepository::class);
        $links->expects($this->never())->method('create_for_post');

        $rewriter = new LinkRewriter($links, 'https://x.test', 'https://x.test/l/');
        $result   = $rewriter->rewrite('no urls here', 42, '');

        $this->assertSame('no urls here', $result);
    }
}
