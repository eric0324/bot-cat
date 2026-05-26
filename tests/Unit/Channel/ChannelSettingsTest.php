<?php
/**
 * @package BotCat\Tests\Unit\Channel
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Channel;

use BotCat\Channel\ChannelSettings;
use BotCat\Tests\TestCase;

/**
 * Covers Requirement: Channel configuration is queryable by other capabilities.
 */
final class ChannelSettingsTest extends TestCase
{
    public function testIsConfiguredReturnsFalseWhenAllFieldsBlank(): void
    {
        $settings = new ChannelSettings('', '', '');

        $this->assertFalse($settings->is_configured());
        $this->assertSame('', $settings->channel_id());
        $this->assertSame('', $settings->channel_secret());
        $this->assertSame('', $settings->access_token());
    }

    public function testIsConfiguredReturnsTrueWhenAllRequiredFieldsPresent(): void
    {
        $settings = new ChannelSettings('1234567890', 'shhh', 'token-xyz');

        $this->assertTrue($settings->is_configured());
    }

    public function testIsConfiguredReturnsFalseWhenAccessTokenIsMissing(): void
    {
        $settings = new ChannelSettings('1234567890', 'shhh', '');

        $this->assertFalse($settings->is_configured());
    }

    public function testFromArrayConstructsFromOptionPayload(): void
    {
        $settings = ChannelSettings::from_array([
            'channel_id'     => '99',
            'channel_secret' => 'sec',
            'access_token'   => 'tok',
        ]);

        $this->assertSame('99', $settings->channel_id());
        $this->assertSame('sec', $settings->channel_secret());
        $this->assertSame('tok', $settings->access_token());
    }

    public function testFromArrayDefaultsMissingKeysToEmptyString(): void
    {
        $settings = ChannelSettings::from_array([]);

        $this->assertFalse($settings->is_configured());
        $this->assertSame('', $settings->channel_id());
    }
}
