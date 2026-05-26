<?php
/**
 * @package BotCat\Tests\Unit\Shortener
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Shortener;

use BotCat\Shortener\ClickLogRepository;
use BotCat\Shortener\ClickRedirectHandler;
use BotCat\Shortener\ShortLink;
use BotCat\Shortener\ShortLinkRepository;
use BotCat\Shortener\SubscriberTokenSigner;
use BotCat\Tests\TestCase;

final class ClickRedirectHandlerTest extends TestCase
{
    public function testValidCodeReturns301WithTargetUrl(): void
    {
        $links = $this->createMock(ShortLinkRepository::class);
        $links->method('find_by_code')->willReturn(new ShortLink(1, 'abc123', 42, 'https://x.test/p/42', ''));

        $clicks = $this->createMock(ClickLogRepository::class);
        $clicks->expects($this->once())->method('log')->with(
            1,
            null,
            $this->matchesRegularExpression('/^[a-f0-9]{64}$/'),
            'curl/8',
            null
        );

        $signer = $this->createMock(SubscriberTokenSigner::class);

        $result = (new ClickRedirectHandler($links, $clicks, $signer, 'salt'))
            ->handle('abc123', null, '203.0.113.1', 'curl/8', null);

        $this->assertSame(301, $result['status']);
        $this->assertSame('https://x.test/p/42', $result['location']);
    }

    public function testUnknownCodeReturns404AndDoesNotLog(): void
    {
        $links = $this->createMock(ShortLinkRepository::class);
        $links->method('find_by_code')->willReturn(null);

        $clicks = $this->createMock(ClickLogRepository::class);
        $clicks->expects($this->never())->method('log');

        $result = (new ClickRedirectHandler(
            $links,
            $clicks,
            $this->createMock(SubscriberTokenSigner::class),
            'salt'
        ))->handle('zzzzzz', null, '127.0.0.1', '', null);

        $this->assertSame(404, $result['status']);
        $this->assertNull($result['location']);
    }

    public function testValidTokenAttributesSubscriber(): void
    {
        $links = $this->createMock(ShortLinkRepository::class);
        $links->method('find_by_code')->willReturn(new ShortLink(1, 'abc123', 42, 'https://x.test/p/42', ''));

        $signer = $this->createMock(SubscriberTokenSigner::class);
        $signer->method('verify')->with('valid-token')->willReturn(101);

        $clicks = $this->createMock(ClickLogRepository::class);
        $clicks->expects($this->once())->method('log')->with(1, 101, $this->anything(), $this->anything(), null);

        (new ClickRedirectHandler($links, $clicks, $signer, 'salt'))
            ->handle('abc123', 'valid-token', '127.0.0.1', null, null);
    }

    public function testTamperedTokenStillRedirectsButAnonymizesAttribution(): void
    {
        $links = $this->createMock(ShortLinkRepository::class);
        $links->method('find_by_code')->willReturn(new ShortLink(1, 'abc123', 42, 'https://x.test/p/42', ''));

        $signer = $this->createMock(SubscriberTokenSigner::class);
        $signer->method('verify')->willReturn(null);

        $clicks = $this->createMock(ClickLogRepository::class);
        $clicks->expects($this->once())->method('log')->with(1, null, $this->anything(), null, null);

        $result = (new ClickRedirectHandler($links, $clicks, $signer, 'salt'))
            ->handle('abc123', 'bad-token', '127.0.0.1', null, null);

        $this->assertSame(301, $result['status']);
        $this->assertSame('https://x.test/p/42', $result['location']);
    }

    public function testSameIpProducesSameHash(): void
    {
        $links = $this->createMock(ShortLinkRepository::class);
        $links->method('find_by_code')->willReturn(new ShortLink(1, 'abc123', 42, 'https://x.test/p/42', ''));

        $hashes = [];
        $clicks = $this->createMock(ClickLogRepository::class);
        $clicks->method('log')->willReturnCallback(function (...$args) use (&$hashes) {
            $hashes[] = $args[2];
        });

        $handler = new ClickRedirectHandler(
            $links,
            $clicks,
            $this->createMock(SubscriberTokenSigner::class),
            'salt'
        );

        $handler->handle('abc123', null, '203.0.113.1', null, null);
        $handler->handle('abc123', null, '203.0.113.1', null, null);

        $this->assertCount(2, $hashes);
        $this->assertSame($hashes[0], $hashes[1], 'same IP must produce same hash');
        $this->assertStringNotContainsString('203.0.113.1', $hashes[0], 'raw IP must NOT appear in hash');
    }
}
