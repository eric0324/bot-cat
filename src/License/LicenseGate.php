<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

use Closure;

/**
 * The ONE helper every Pro feature consults.
 *
 * Pro features MUST call `LicenseGate::is_pro_active()` (typically
 * via the `botcat_is_pro` filter, which Plugin wires this gate into).
 * They MUST NOT read the underlying options directly — `grep
 * botcat_license_status` should only ever turn up the LicenseRepository
 * and this gate.
 *
 * On the Free distribution the License source isn't bundled at all,
 * so this class is simply absent and `Edition::is_pro()` short-circuits
 * to false without any HTTP or option work.
 */
class LicenseGate {

	/**
	 * @param Closure(): int|null $now_provider Override for tests; defaults to time().
	 */
	public function __construct(
		private readonly LicenseRepository $repository,
		private readonly LicenseStatusEvaluator $evaluator,
		private readonly ?Closure $now_provider = null
	) {
	}

	public function is_pro_active(): bool {
		$cache = $this->repository->get_cache();
		$now   = $this->now_provider !== null ? (int) ( $this->now_provider )() : time();

		return $this->evaluator->evaluate( $cache, $now ) === LicenseStatus::ACTIVE;
	}

	public function filter_is_pro( bool $current ): bool {
		return $current || $this->is_pro_active();
	}
}
