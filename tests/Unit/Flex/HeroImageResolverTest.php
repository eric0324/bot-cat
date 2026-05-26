<?php
/**
 * @package BotCat\Tests\Unit\Flex
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Flex;

use BotCat\Flex\FlexTemplate;
use BotCat\Flex\HeroImageResolver;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class HeroImageResolverTest extends TestCase
{
    public function testFeaturedImageWinsWhenAvailable(): void
    {
        Functions\when('has_post_thumbnail')->alias(static fn() => true);
        Functions\when('get_post_thumbnail_id')->alias(static fn() => 99);
        Functions\when('wp_get_attachment_image_src')->alias(static fn() => ['https://x.test/img.jpg', 1024, 768]);

        $url = (new HeroImageResolver())->resolve(FlexTemplate::HERO_FEATURED, '', 7);

        $this->assertSame('https://x.test/img.jpg', $url);
    }

    public function testFallsBackToAttachedWhenFeaturedAbsent(): void
    {
        Functions\when('has_post_thumbnail')->alias(static fn() => false);
        Functions\when('get_attached_media')->alias(static function ($mime, $post_id) {
            $attachment        = new \stdClass();
            $attachment->ID    = 200;
            return [$attachment];
        });
        Functions\when('wp_get_attachment_image_src')->alias(static fn() => ['https://x.test/attached.jpg']);

        $url = (new HeroImageResolver())->resolve(FlexTemplate::HERO_FEATURED, '', 7);

        $this->assertSame('https://x.test/attached.jpg', $url);
    }

    public function testFallsBackToDefaultUrl(): void
    {
        Functions\when('has_post_thumbnail')->alias(static fn() => false);
        Functions\when('get_attached_media')->alias(static fn() => []);

        $url = (new HeroImageResolver())->resolve(FlexTemplate::HERO_FEATURED, 'https://default.test/img.jpg', 7);

        $this->assertSame('https://default.test/img.jpg', $url);
    }

    public function testReturnsNullWhenNothingAvailable(): void
    {
        Functions\when('has_post_thumbnail')->alias(static fn() => false);
        Functions\when('get_attached_media')->alias(static fn() => []);

        $url = (new HeroImageResolver())->resolve(FlexTemplate::HERO_FEATURED, '', 7);

        $this->assertNull($url);
    }

    public function testStrategyNoneReturnsNullEvenWhenImagesPresent(): void
    {
        Functions\when('has_post_thumbnail')->alias(static fn() => true);
        Functions\when('get_post_thumbnail_id')->alias(static fn() => 99);
        Functions\when('wp_get_attachment_image_src')->alias(static fn() => ['https://x.test/img.jpg']);

        $url = (new HeroImageResolver())->resolve(FlexTemplate::HERO_NONE, '', 7);

        $this->assertNull($url);
    }
}
