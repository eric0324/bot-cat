<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

/**
 * Defines and installs the plugin's MySQL tables.
 *
 * Each table is prefixed `{$wpdb->prefix}botcat_` per the spec. `install()`
 * delegates to WordPress core's `dbDelta()` so repeat invocations are
 * idempotent.
 */
class Schema {

	public const TABLES = array( 'subscribers', 'push_jobs', 'push_logs', 'tags', 'subscriber_tags', 'short_links', 'short_link_clicks' );

	/**
	 * @return list<string> Fully qualified table names (prefix included).
	 */
	public function tables(): array {
		global $wpdb;
		$prefix = $wpdb->prefix;

		return array_map(
			static fn( string $name ): string => $prefix . 'botcat_' . $name,
			self::TABLES
		);
	}

	public function install(): void {
		if ( ! function_exists( 'dbDelta' ) ) {
			$upgrade = ABSPATH . 'wp-admin/includes/upgrade.php';
			if ( file_exists( $upgrade ) ) {
				require_once $upgrade;
			}
		}

		foreach ( self::TABLES as $table ) {
			dbDelta( $this->sql_for( $table ) );
		}
	}

	public function sql_for( string $table ): string {
		global $wpdb;
		$prefix          = $wpdb->prefix . 'botcat_';
		$charset_collate = $this->charset_collate();

		return match ( $table ) {
			'subscribers' => <<<SQL
CREATE TABLE {$prefix}subscribers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    line_user_id VARCHAR(64) NOT NULL,
    display_name VARCHAR(255) NULL,
    picture_url TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    followed_at DATETIME NULL,
    unfollowed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY line_user_id (line_user_id),
    KEY status (status)
) {$charset_collate};
SQL
,
			'push_jobs' => <<<SQL
CREATE TABLE {$prefix}push_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id BIGINT UNSIGNED NOT NULL,
    post_type VARCHAR(40) NOT NULL DEFAULT 'post',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
    sent_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    is_test TINYINT(1) NOT NULL DEFAULT 0,
    last_error TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    triggered_at DATETIME NULL,
    started_at DATETIME NULL,
    finished_at DATETIME NULL,
    PRIMARY KEY  (id),
    KEY post_status (post_id, status),
    KEY status_triggered (status, triggered_at),
    KEY triggered_at (triggered_at)
) {$charset_collate};
SQL
,
			'push_logs' => <<<SQL
CREATE TABLE {$prefix}push_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id BIGINT UNSIGNED NOT NULL,
    subscriber_id BIGINT UNSIGNED NULL,
    line_user_id VARCHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    error TEXT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_test TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    PRIMARY KEY  (id),
    KEY job_status (job_id, status),
    KEY subscriber_id (subscriber_id),
    KEY created_at (created_at)
) {$charset_collate};
SQL
,
			'tags' => <<<SQL
CREATE TABLE {$prefix}tags (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(64) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    keyword VARCHAR(128) NOT NULL,
    mapped_categories TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY slug (slug),
    KEY keyword (keyword)
) {$charset_collate};
SQL
,
			'subscriber_tags' => <<<SQL
CREATE TABLE {$prefix}subscriber_tags (
    subscriber_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (subscriber_id, tag_id),
    KEY tag_subscriber (tag_id, subscriber_id)
) {$charset_collate};
SQL
,
			'short_links' => <<<SQL
CREATE TABLE {$prefix}short_links (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(12) NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    original_url TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY code (code),
    KEY post_id (post_id)
) {$charset_collate};
SQL
,
			'short_link_clicks' => <<<SQL
CREATE TABLE {$prefix}short_link_clicks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    short_link_id BIGINT UNSIGNED NOT NULL,
    subscriber_id BIGINT UNSIGNED NULL,
    ip_hash CHAR(64) NOT NULL,
    user_agent VARCHAR(255) NULL,
    referer VARCHAR(255) NULL,
    clicked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY short_link_id (short_link_id, clicked_at),
    KEY ip_hash (ip_hash)
) {$charset_collate};
SQL
,
			default => '',
		};
	}

	private function charset_collate(): string {
		global $wpdb;

		if ( method_exists( $wpdb, 'get_charset_collate' ) ) {
			return $wpdb->get_charset_collate();
		}

		$charset = $wpdb->charset ?? 'utf8mb4';
		$collate = $wpdb->collate ?? 'utf8mb4_unicode_ci';

		return "DEFAULT CHARACTER SET {$charset} COLLATE {$collate}";
	}
}
