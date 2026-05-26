<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

/**
 * Tracks the schema version that's been applied to this site and runs
 * pending migrations on activation and on every admin request.
 */
final class SchemaVersion {

	public const OPTION = 'botcat_db_version';

	public function __construct(
		private readonly Schema $schema,
		private readonly string $bundled_version
	) {
	}

	public function maybe_upgrade(): void {
		$stored = (string) get_option( self::OPTION, '0.0.0' );

		if ( version_compare( $stored, $this->bundled_version, '>=' ) ) {
			return;
		}

		$this->schema->install();
		update_option( self::OPTION, $this->bundled_version );
	}
}
