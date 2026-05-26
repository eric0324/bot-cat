<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * Snapshot of the most recent Polar revalidation result.
 *
 * Persisted as JSON in option `botcat_license_cache`.
 */
final class LicenseCache {

	public function __construct(
		public readonly bool $entitled,
		public readonly int $verified_at,
		public readonly ?int $last_failure_at,
		public readonly int $consecutive_failures,
		public readonly ?string $plan_name,
		public readonly ?string $renews_at
	) {
	}

	public static function empty(): self {
		return new self( false, 0, null, 0, null, null );
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function from_array( array $payload ): self {
		return new self(
			entitled: ! empty( $payload['entitled'] ),
			verified_at: (int) ( $payload['verified_at'] ?? 0 ),
			last_failure_at: isset( $payload['last_failure_at'] ) ? (int) $payload['last_failure_at'] : null,
			consecutive_failures: (int) ( $payload['consecutive_failures'] ?? 0 ),
			plan_name: isset( $payload['plan_name'] ) ? (string) $payload['plan_name'] : null,
			renews_at: isset( $payload['renews_at'] ) ? (string) $payload['renews_at'] : null,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'entitled'             => $this->entitled,
			'verified_at'          => $this->verified_at,
			'last_failure_at'      => $this->last_failure_at,
			'consecutive_failures' => $this->consecutive_failures,
			'plan_name'            => $this->plan_name,
			'renews_at'            => $this->renews_at,
		);
	}
}
