<?php
/**
 * @package BotCat\Channel
 */

declare(strict_types=1);

namespace BotCat\Channel;

/**
 * Immutable value object holding LINE Channel credentials.
 *
 * Other capabilities consume this through {@see ChannelSettingsRepository::get()}
 * rather than reading the raw option — see line-channel spec.
 */
final class ChannelSettings {

	public function __construct(
		private readonly string $channel_id,
		private readonly string $channel_secret,
		private readonly string $access_token
	) {
	}

	public function channel_id(): string {
		return $this->channel_id;
	}

	public function channel_secret(): string {
		return $this->channel_secret;
	}

	public function access_token(): string {
		return $this->access_token;
	}

	public function is_configured(): bool {
		return $this->channel_id !== ''
			&& $this->channel_secret !== ''
			&& $this->access_token !== '';
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function from_array( array $data ): self {
		return new self(
			(string) ( $data['channel_id'] ?? '' ),
			(string) ( $data['channel_secret'] ?? '' ),
			(string) ( $data['access_token'] ?? '' )
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function to_array(): array {
		return array(
			'channel_id'     => $this->channel_id,
			'channel_secret' => $this->channel_secret,
			'access_token'   => $this->access_token,
		);
	}
}
