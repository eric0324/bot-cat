<?php
/**
 * @package BotCat\Tests\Unit\Tags
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Tags;

use BotCat\Tags\TagCommand;
use BotCat\Tags\TagCommandParser;
use BotCat\Tests\TestCase;

final class TagCommandParserTest extends TestCase
{
    public function testNonCommandTextIsClassifiedAsSuch(): void
    {
        $cmd = (new TagCommandParser())->parse('hello world');

        $this->assertSame(TagCommand::TYPE_NOT_A_COMMAND, $cmd->type);
    }

    public function testSubscribeChinese(): void
    {
        $cmd = (new TagCommandParser())->parse('/tag 訂閱 科技');

        $this->assertSame(TagCommand::TYPE_SUBSCRIBE, $cmd->type);
        $this->assertSame('科技', $cmd->keyword);
    }

    public function testSubscribeEnglish(): void
    {
        $cmd = (new TagCommandParser())->parse('/tag subscribe tech');

        $this->assertSame(TagCommand::TYPE_SUBSCRIBE, $cmd->type);
        $this->assertSame('tech', $cmd->keyword);
    }

    public function testUnsubscribeChinese(): void
    {
        $cmd = (new TagCommandParser())->parse('/tag 取消 科技');

        $this->assertSame(TagCommand::TYPE_UNSUBSCRIBE, $cmd->type);
        $this->assertSame('科技', $cmd->keyword);
    }

    public function testUnsubscribeEnglish(): void
    {
        $cmd = (new TagCommandParser())->parse('/tag unsubscribe tech');

        $this->assertSame(TagCommand::TYPE_UNSUBSCRIBE, $cmd->type);
        $this->assertSame('tech', $cmd->keyword);
    }

    public function testListChinese(): void
    {
        $cmd = (new TagCommandParser())->parse('/tag 列表');

        $this->assertSame(TagCommand::TYPE_LIST, $cmd->type);
    }

    public function testListEnglish(): void
    {
        $cmd = (new TagCommandParser())->parse('/tag list');

        $this->assertSame(TagCommand::TYPE_LIST, $cmd->type);
    }

    public function testLeadingWhitespaceIsForgiving(): void
    {
        $cmd = (new TagCommandParser())->parse("   /tag list   ");

        $this->assertSame(TagCommand::TYPE_LIST, $cmd->type);
    }

    public function testTagPrefixWithUnknownSubcommandIsUnknown(): void
    {
        $cmd = (new TagCommandParser())->parse('/tag foo bar');

        $this->assertSame(TagCommand::TYPE_UNKNOWN, $cmd->type);
    }

    public function testKeywordWithSpacesIsCapturedVerbatim(): void
    {
        $cmd = (new TagCommandParser())->parse('/tag 訂閱 多字 keyword');

        $this->assertSame('多字 keyword', $cmd->keyword);
    }
}
