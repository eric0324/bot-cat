<?php
/**
 * @package BotCat\Tests\Unit\Channel
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Channel;

use BotCat\Channel\ChannelSettings;
use BotCat\Channel\ChannelSettingsRepository;
use BotCat\Channel\CredentialEncryptor;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Scenario: Stored option is opaque (secrets are saved encrypted)
 * and Scenario: Decryption requires the site key.
 */
final class ChannelSettingsRepositoryTest extends TestCase
{
    public function testSaveStoresOptionWithCiphertextNotPlaintext(): void
    {
        $encryptor = new CredentialEncryptor('site-A-auth-key-32-chars-padding-AAA');
        $repo      = new ChannelSettingsRepository($encryptor);

        $stored = null;
        Functions\expect('update_option')->once()
            ->andReturnUsing(function (string $option, $value) use (&$stored): bool {
                $stored = ['option' => $option, 'value' => $value];
                return true;
            });

        $repo->save(new ChannelSettings('123', 'plain-secret', 'plain-token'));

        $this->assertSame('botcat_channel_settings', $stored['option']);
        $this->assertSame('123', $stored['value']['channel_id']);
        $this->assertStringStartsWith(CredentialEncryptor::SCHEME, $stored['value']['channel_secret']);
        $this->assertStringStartsWith(CredentialEncryptor::SCHEME, $stored['value']['access_token']);
        $this->assertStringNotContainsString('plain-secret', $stored['value']['channel_secret']);
        $this->assertStringNotContainsString('plain-token', $stored['value']['access_token']);
    }

    public function testGetDecryptsCiphertextBackToPlaintext(): void
    {
        $encryptor = new CredentialEncryptor('site-A-auth-key-32-chars-padding-AAA');
        $repo      = new ChannelSettingsRepository($encryptor);

        $stored = [
            'channel_id'     => '123',
            'channel_secret' => $encryptor->encrypt('plain-secret'),
            'access_token'   => $encryptor->encrypt('plain-token'),
        ];

        Functions\expect('get_option')->once()
            ->with('botcat_channel_settings', [])
            ->andReturn($stored);

        $settings = $repo->get();

        $this->assertSame('123', $settings->channel_id());
        $this->assertSame('plain-secret', $settings->channel_secret());
        $this->assertSame('plain-token', $settings->access_token());
        $this->assertTrue($settings->is_configured());
    }

    public function testGetReturnsEmptySettingsWhenOptionIsMissing(): void
    {
        $encryptor = new CredentialEncryptor('site-A-auth-key-32-chars-padding-AAA');
        $repo      = new ChannelSettingsRepository($encryptor);

        Functions\expect('get_option')->once()
            ->with('botcat_channel_settings', [])
            ->andReturn([]);

        $settings = $repo->get();

        $this->assertFalse($settings->is_configured());
    }

    public function testGetTreatsUndecipherableCiphertextAsEmpty(): void
    {
        $alice = new CredentialEncryptor('alice-key-32-chars-padding-AAA-xyz');
        $bob   = new CredentialEncryptor('bob-key-32-chars-padding-BBB-uvw-zz');

        $stored = [
            'channel_id'     => '123',
            'channel_secret' => $alice->encrypt('plain-secret'),
            'access_token'   => $alice->encrypt('plain-token'),
        ];

        Functions\expect('get_option')->once()->andReturn($stored);

        $bob_repo = new ChannelSettingsRepository($bob);
        $settings = $bob_repo->get();

        $this->assertSame('123', $settings->channel_id());
        $this->assertSame('', $settings->channel_secret(), 'bob cannot decrypt alice secret');
        $this->assertSame('', $settings->access_token());
        $this->assertFalse($settings->is_configured(), 'cross-site export should not stay configured');
    }
}
