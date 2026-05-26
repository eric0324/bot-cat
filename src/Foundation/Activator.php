<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

use Closure;

/**
 * Runs on `register_activation_hook`. Refuses to install on unsupported
 * runtimes, otherwise creates tables and schedules the retention cron.
 */
final class Activator {

	public const CRON_HOOK = 'botcat_cleanup_old_logs';
	public const MIN_PHP   = '8.1';
	public const MIN_WP    = '7.0';

	/**
	 * @param Closure|null $abort_handler Optional override for the abort path; mostly for tests.
	 */
	public function __construct(
		private readonly Schema $schema,
		private readonly ?Closure $abort_handler = null
	) {
	}

	public static function activate(): void {
		global $wp_version;

		$self = new self( new Schema() );
		$self->run( PHP_VERSION, (string) $wp_version );
	}

	public function run( string $php_version, string $wp_version ): void {
		if ( version_compare( $php_version, self::MIN_PHP, '<' ) ) {
			$this->abort(
				sprintf(
					/* translators: %1$s: required PHP version, %2$s: actual PHP version. */
					__( 'bot-cat requires PHP %1$s or newer. You are running PHP %2$s.', 'bot-cat' ),
					self::MIN_PHP,
					$php_version
				)
			);
			return;
		}

		if ( version_compare( $wp_version, self::MIN_WP, '<' ) ) {
			$this->abort(
				sprintf(
					/* translators: %1$s: required WordPress version, %2$s: actual WordPress version. */
					__( 'bot-cat requires WordPress %1$s or newer. You are running WordPress %2$s.', 'bot-cat' ),
					self::MIN_WP,
					$wp_version
				)
			);
			return;
		}

		$this->schema->install();

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	private function abort( string $message ): void {
		if ( $this->abort_handler !== null ) {
			( $this->abort_handler )( $message );
		}

		deactivate_plugins( plugin_basename( BOT_CAT_FILE ) );
		wp_die( esc_html( $message ) );
	}
}
