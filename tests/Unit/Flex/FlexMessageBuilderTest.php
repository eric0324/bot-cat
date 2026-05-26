<?php
/**
 * @package BotCat\Tests\Unit\Flex
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Flex;

use BotCat\Flex\FlexMessageBuilder;
use BotCat\Flex\FlexTemplate;
use BotCat\Flex\HeroImageResolver;
use BotCat\Template\TemplateRenderer;
use BotCat\Template\TokenResolver;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class FlexMessageBuilderTest extends TestCase
{
    public function testBuildsBubbleWithHeroBodyAndFooter(): void
    {
        $resolver = $this->createMock(TokenResolver::class);
        $resolver->method('resolve_for_post')->willReturn([
            'title'     => 'Headline X',
            'excerpt'   => 'Excerpt Y',
            'permalink' => 'https://x.test/p/1',
        ]);

        $hero = $this->createMock(HeroImageResolver::class);
        $hero->method('resolve')->willReturn('https://x.test/img.jpg');

        $template = new FlexTemplate(
            FlexTemplate::HERO_FEATURED,
            '',
            '🆕 {title}',
            '{excerpt}',
            [['label' => 'Read', 'url_template' => '{permalink}']]
        );

        $result = (new FlexMessageBuilder(new TemplateRenderer($resolver), $hero))
            ->build($template, $this->post(1));

        $this->assertSame('flex', $result['type']);
        $this->assertArrayHasKey('altText', $result);
        $this->assertSame('bubble', $result['contents']['type']);
        $this->assertSame('https://x.test/img.jpg', $result['contents']['hero']['url']);

        $body_texts = array_column($result['contents']['body']['contents'], 'text');
        $this->assertContains('🆕 Headline X', $body_texts);
        $this->assertContains('Excerpt Y', $body_texts);

        $cta = $result['contents']['footer']['contents'][0];
        $this->assertSame('Read', $cta['action']['label']);
        $this->assertSame('https://x.test/p/1', $cta['action']['uri']);
    }

    public function testOmitsHeroBlockWhenResolverReturnsNull(): void
    {
        $resolver = $this->createMock(TokenResolver::class);
        $resolver->method('resolve_for_post')->willReturn(['title' => 't', 'excerpt' => 'e']);

        $hero = $this->createMock(HeroImageResolver::class);
        $hero->method('resolve')->willReturn(null);

        $template = new FlexTemplate(FlexTemplate::HERO_NONE, '', '{title}', '{excerpt}', []);

        $result = (new FlexMessageBuilder(new TemplateRenderer($resolver), $hero))
            ->build($template, $this->post(1));

        $this->assertArrayNotHasKey('hero', $result['contents']);
    }

    public function testAltTextIsCappedAt400Chars(): void
    {
        $resolver = $this->createMock(TokenResolver::class);
        $resolver->method('resolve_for_post')->willReturn([
            'title'   => str_repeat('a', 500),
            'excerpt' => str_repeat('b', 500),
        ]);

        $hero = $this->createMock(HeroImageResolver::class);
        $hero->method('resolve')->willReturn(null);

        $template = new FlexTemplate(FlexTemplate::HERO_NONE, '', '{title}', '{excerpt}', []);

        $result = (new FlexMessageBuilder(new TemplateRenderer($resolver), $hero))
            ->build($template, $this->post(1));

        $this->assertLessThanOrEqual(400, mb_strlen($result['altText'], 'UTF-8'));
    }

    public function testCtaCountCappedAtThree(): void
    {
        $resolver = $this->createMock(TokenResolver::class);
        $resolver->method('resolve_for_post')->willReturn(['permalink' => 'u']);

        $hero = $this->createMock(HeroImageResolver::class);
        $hero->method('resolve')->willReturn(null);

        $template = new FlexTemplate(FlexTemplate::HERO_NONE, '', 'h', 'b', [
            ['label' => 'A', 'url_template' => '{permalink}'],
            ['label' => 'B', 'url_template' => '{permalink}'],
            ['label' => 'C', 'url_template' => '{permalink}'],
            ['label' => 'D', 'url_template' => '{permalink}'],
        ]);

        $result = (new FlexMessageBuilder(new TemplateRenderer($resolver), $hero))
            ->build($template, $this->post(1));

        $this->assertCount(3, $result['contents']['footer']['contents']);
    }

    private function post(int $id): object
    {
        $p     = new \stdClass();
        $p->ID = $id;
        return $p;
    }
}
