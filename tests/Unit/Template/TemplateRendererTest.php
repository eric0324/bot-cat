<?php
/**
 * @package BotCat\Tests\Unit\Template
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Template;

use BotCat\Template\TemplateRenderer;
use BotCat\Template\TokenResolver;
use BotCat\Tests\TestCase;

final class TemplateRendererTest extends TestCase
{
    public function testReplacesEveryKnownTokenInTemplate(): void
    {
        $resolver = $this->createMock(TokenResolver::class);
        $resolver->method('resolve_for_post')->willReturn([
            'title'     => 'Hello World',
            'excerpt'   => 'Short summary.',
            'permalink' => 'https://x.test/p/1',
            'author'    => 'Eric',
            'category'  => 'News',
            'date'      => '2026-05-26 10:00',
            'site_name' => 'Cat Site',
        ]);

        $rendered = (new TemplateRenderer($resolver))->render(
            "{title}\n{excerpt}\n{permalink}\nby {author} in {category} · {date} · {site_name}",
            new \stdClass()
        );

        $this->assertSame(
            "Hello World\nShort summary.\nhttps://x.test/p/1\nby Eric in News · 2026-05-26 10:00 · Cat Site",
            $rendered
        );
    }

    public function testUnknownTokensAreLeftAsLiteralText(): void
    {
        $resolver = $this->createMock(TokenResolver::class);
        $resolver->method('resolve_for_post')->willReturn(['title' => 'X']);

        $rendered = (new TemplateRenderer($resolver))->render('{title} and {foo}', new \stdClass());

        $this->assertSame('X and {foo}', $rendered, 'unknown tokens stay literal per spec');
    }

    public function testPreservesNewlinesAndEmojiAsUtf8(): void
    {
        $resolver = $this->createMock(TokenResolver::class);
        $resolver->method('resolve_for_post')->willReturn(['title' => "Hello 🐱\nMultiline"]);

        $rendered = (new TemplateRenderer($resolver))->render('Title: {title}', new \stdClass());

        $this->assertSame("Title: Hello 🐱\nMultiline", $rendered);
        $this->assertTrue(mb_check_encoding($rendered, 'UTF-8'));
    }

    public function testEmptyTemplateProducesEmptyOutput(): void
    {
        $resolver = $this->createMock(TokenResolver::class);
        $resolver->method('resolve_for_post')->willReturn(['title' => 'X']);

        $this->assertSame('', (new TemplateRenderer($resolver))->render('', new \stdClass()));
    }
}
