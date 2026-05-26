<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Flex\FlexMessageBuilder;
use BotCat\Flex\FlexTemplate;
use BotCat\Flex\FlexTemplateRepository;
use BotCat\Foundation\Edition;
use BotCat\Push\MessageBuilder;
use BotCat\Push\PushJob;
use BotCat\Template\TemplateRenderer;
use BotCat\Template\TemplateRepository;
use BotCat\Tests\TestCase;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

final class MessageBuilderFlexTest extends TestCase
{
    public function testProEditionReturnsFlexPayload(): void
    {
        Functions\when('get_post')->alias(fn() => $this->fake_post());

        $edition = $this->createMock(Edition::class);
        $edition->method('is_pro')->willReturn(true);

        $flex_repo = $this->createMock(FlexTemplateRepository::class);
        $flex_repo->method('get')->willReturn(new FlexTemplate(FlexTemplate::HERO_NONE, '', 'h', 'b', []));

        $flex_builder = $this->createMock(FlexMessageBuilder::class);
        $flex_builder->method('build')->willReturn(['type' => 'flex', 'altText' => 'x']);

        $builder = new MessageBuilder(null, null, $flex_repo, $flex_builder, $edition);
        $result  = $builder->build_for_job($this->job_with_post(7));

        $this->assertSame('flex', $result[0]['type']);
    }

    public function testFlexExceptionTriggersTextFallbackAndFiresAction(): void
    {
        Functions\when('get_post')->alias(fn() => $this->fake_post());
        Functions\when('get_the_title')->alias(fn() => 'Title');
        Functions\when('get_permalink')->alias(fn() => 'https://x.test/p/7');

        $edition = $this->createMock(Edition::class);
        $edition->method('is_pro')->willReturn(true);

        $flex_repo    = $this->createMock(FlexTemplateRepository::class);
        $flex_repo->method('get')->willReturn(new FlexTemplate(FlexTemplate::HERO_NONE, '', 'h', 'b', []));

        $flex_builder = $this->createMock(FlexMessageBuilder::class);
        $flex_builder->method('build')->willThrowException(new \RuntimeException('boom'));

        Actions\expectDone('botcat_flex_render_failed')->once();

        $builder = new MessageBuilder(null, null, $flex_repo, $flex_builder, $edition);
        $result  = $builder->build_for_job($this->job_with_post(7));

        $this->assertSame('text', $result[0]['type']);
        $this->assertStringContainsString('Title', $result[0]['text']);
    }

    public function testFreeEditionAlwaysUsesText(): void
    {
        Functions\when('get_the_title')->alias(fn() => 'Title');
        Functions\when('get_permalink')->alias(fn() => 'https://x.test/p/7');

        $edition = $this->createMock(Edition::class);
        $edition->method('is_pro')->willReturn(false);

        $flex_repo    = $this->createMock(FlexTemplateRepository::class);
        $flex_repo->expects($this->never())->method('get');

        $flex_builder = $this->createMock(FlexMessageBuilder::class);
        $flex_builder->expects($this->never())->method('build');

        $builder = new MessageBuilder(null, null, $flex_repo, $flex_builder, $edition);
        $result  = $builder->build_for_job($this->job_with_post(7));

        $this->assertSame('text', $result[0]['type']);
    }

    private function fake_post(): object
    {
        $p     = new \stdClass();
        $p->ID = 7;
        return $p;
    }

    private function job_with_post(int $post_id): PushJob
    {
        return new PushJob(
            id: 1, post_id: $post_id, post_type: 'post', status: 'pending',
            recipient_count: 0, sent_count: 0, failed_count: 0,
            is_test: false, last_error: null,
            created_at: '', triggered_at: null, started_at: null, finished_at: null
        );
    }
}
