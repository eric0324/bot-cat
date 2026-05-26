<?php
/**
 * @package BotCat\Tests\Unit\Flex
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Flex;

use BotCat\Flex\FlexTemplate;
use BotCat\Flex\FlexTemplateRepository;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class FlexTemplateRepositoryTest extends TestCase
{
    public function testDefaultIsReturnedWhenOptionMissing(): void
    {
        Functions\when('get_option')->alias(static fn() => false);

        $template = (new FlexTemplateRepository())->get();

        $this->assertSame(FlexTemplate::HERO_FEATURED, $template->hero_source);
        $this->assertNotEmpty($template->headline);
    }

    public function testStoredArrayHydratesToTemplate(): void
    {
        Functions\when('get_option')->alias(static fn() => [
            'hero_source'       => 'attached',
            'default_image_url' => 'https://x.test/img.jpg',
            'headline'          => '📝 {title}',
            'body'              => '{excerpt}',
            'ctas'              => [
                ['label' => 'Read', 'url_template' => '{permalink}'],
            ],
        ]);

        $template = (new FlexTemplateRepository())->get();

        $this->assertSame('attached', $template->hero_source);
        $this->assertSame('https://x.test/img.jpg', $template->default_image_url);
        $this->assertSame('📝 {title}', $template->headline);
        $this->assertCount(1, $template->ctas);
    }

    public function testSaveCapsCtasAtThree(): void
    {
        $captured = null;
        Functions\when('update_option')->alias(static function ($name, $value) use (&$captured) {
            $captured = $value;
            return true;
        });

        $repo = new FlexTemplateRepository();
        $repo->save(new FlexTemplate(
            FlexTemplate::HERO_FEATURED, '', 'h', 'b',
            [
                ['label' => 'A', 'url_template' => '{permalink}'],
                ['label' => 'B', 'url_template' => '{permalink}'],
                ['label' => 'C', 'url_template' => '{permalink}'],
                ['label' => 'D', 'url_template' => '{permalink}'],
            ]
        ));

        $this->assertNotNull($captured);
        $this->assertCount(3, $captured['ctas']);
    }
}
