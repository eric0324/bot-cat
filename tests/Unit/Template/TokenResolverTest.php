<?php
/**
 * @package BotCat\Tests\Unit\Template
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Template;

use BotCat\Template\TokenResolver;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class TokenResolverTest extends TestCase
{
    public function testResolvesEveryStandardToken(): void
    {
        $post = $this->build_post();

        Functions\expect('get_the_title')->andReturn('Title 1');
        Functions\expect('get_permalink')->andReturn('https://example.test/post/1');
        Functions\expect('get_the_author_meta')->with('display_name', 17)->andReturn('Eric');
        Functions\expect('wp_get_post_categories')->with(1, ['fields' => 'names'])->andReturn(['News']);
        Functions\expect('get_bloginfo')->with('name')->andReturn('Cat Site');
        Functions\stubs(['get_date_from_gmt' => static fn(string $g, string $f): string => '2026-05-26 10:00']);

        $values = (new TokenResolver())->resolve_for_post($post);

        $this->assertSame('Title 1', $values['title']);
        $this->assertSame('https://example.test/post/1', $values['permalink']);
        $this->assertSame('Eric', $values['author']);
        $this->assertSame('News', $values['category']);
        $this->assertSame('2026-05-26 10:00', $values['date']);
        $this->assertSame('Cat Site', $values['site_name']);
    }

    public function testUsesAuthoredExcerptWhenPresent(): void
    {
        $post = $this->build_post(excerpt: 'hand-written summary', content: '<p>body</p>');
        $this->stub_basics();

        $values = (new TokenResolver())->resolve_for_post($post);

        $this->assertSame('hand-written summary', $values['excerpt']);
    }

    public function testFallsBackToContentForExcerpt(): void
    {
        $post = $this->build_post(excerpt: '', content: '<p>Hello <strong>world</strong></p>');
        $this->stub_basics();

        $values = (new TokenResolver())->resolve_for_post($post);

        $this->assertSame('Hello world', $values['excerpt']);
    }

    public function testCategoryReturnsFirstWhenMultiple(): void
    {
        $post = $this->build_post();
        Functions\expect('get_the_title')->andReturn('t');
        Functions\expect('get_permalink')->andReturn('u');
        Functions\expect('get_the_author_meta')->andReturn('a');
        Functions\expect('wp_get_post_categories')->andReturn(['Primary', 'Secondary']);
        Functions\expect('get_bloginfo')->andReturn('s');
        Functions\stubs(['get_date_from_gmt' => static fn(string $g, string $f): string => '2026']);

        $values = (new TokenResolver())->resolve_for_post($post);

        $this->assertSame('Primary', $values['category']);
    }

    public function testCategoryReturnsEmptyWhenNoCategories(): void
    {
        $post = $this->build_post();
        Functions\expect('get_the_title')->andReturn('t');
        Functions\expect('get_permalink')->andReturn('u');
        Functions\expect('get_the_author_meta')->andReturn('a');
        Functions\expect('wp_get_post_categories')->andReturn([]);
        Functions\expect('get_bloginfo')->andReturn('s');
        Functions\stubs(['get_date_from_gmt' => static fn(string $g, string $f): string => '2026']);

        $this->assertSame('', (new TokenResolver())->resolve_for_post($post)['category']);
    }

    private function stub_basics(): void
    {
        Functions\expect('get_the_title')->andReturn('t');
        Functions\expect('get_permalink')->andReturn('u');
        Functions\expect('get_the_author_meta')->andReturn('a');
        Functions\expect('wp_get_post_categories')->andReturn(['c']);
        Functions\expect('get_bloginfo')->andReturn('s');
        Functions\stubs(['get_date_from_gmt' => static fn(string $g, string $f): string => '2026']);
    }

    private function build_post(string $excerpt = '', string $content = ''): object
    {
        $p = new \stdClass();
        $p->ID = 1;
        $p->post_author = 17;
        $p->post_excerpt = $excerpt;
        $p->post_content = $content;
        $p->post_date_gmt = '2026-05-26 02:00:00';
        return $p;
    }
}
