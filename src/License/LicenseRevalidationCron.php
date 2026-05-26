<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * Daily cron that re-confirms the stored license with Polar.
 *
 *  - Successful entitled response → advances verified_at, resets the
 *    failure counter, keeps status active.
 *  - Successful but not entitled → records the no-op (verified_at
 *    stays fixed; the evaluator decides EXPIRED once the cache crosses
 *    the grace threshold).
 *  - Network failure → bumps consecutive_failures so the admin can
 *    see why the cache is stale.
 */
class LicenseRevalidationCron {

	public const HOOK = 'botcat_license_revalidate';

	public function __construct(
		private readonly LicenseRepository $repository,
		private readonly PolarClient $polar
	) {
	}

	public function run(): void {
		$key = $this->repository->get_key();
		if ( $key === '' ) {
			return;
		}

		$result = $this->polar->validate( $key );
		$cache  = $this->repository->get_cache();
		$now    = time();

		if ( ! $result->ok ) {
			$this->repository->save_cache(
				new LicenseCache(
					entitled: $cache->entitled,
					verified_at: $cache->verified_at,
					last_failure_at: $now,
					consecutive_failures: $cache->consecutive_failures + 1,
					plan_name: $cache->plan_name,
					renews_at: $cache->renews_at,
				)
			);
			return;
		}

		if ( ! $result->entitled ) {
			$this->repository->save_cache(
				new LicenseCache(
					entitled: false,
					verified_at: $cache->verified_at,
					last_failure_at: $now,
					consecutive_failures: $cache->consecutive_failures + 1,
					plan_name: $result->plan_name ?? $cache->plan_name,
					renews_at: $result->renews_at ?? $cache->renews_at,
				)
			);
			$this->maybe_expire( $cache->verified_at, $now );
			return;
		}

		$this->repository->save_cache(
			new LicenseCache(
				entitled: true,
				verified_at: $now,
				last_failure_at: null,
				consecutive_failures: 0,
				plan_name: $result->plan_name ?? $cache->plan_name,
				renews_at: $result->renews_at ?? $cache->renews_at,
			)
		);
		$this->repository->save_status( LicenseStatus::ACTIVE );
	}

	private function maybe_expire( int $verified_at, int $now ): void {
		if ( $verified_at === 0 ) {
			return;
		}
		if ( $now - $verified_at > LicenseStatusEvaluator::GRACE_SECONDS ) {
			$this->repository->save_status( LicenseStatus::EXPIRED );
		}
	}
}
