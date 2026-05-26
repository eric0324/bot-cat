<?php
/**
 * @package BotCat\Shortener
 */

declare(strict_types=1);

namespace BotCat\Shortener;

/**
 * CRUD for `botcat_short_links`.
 *
 * `create_for_post()` is find-or-create: the same post + URL pair will
 * return the same short code on subsequent calls so the short URL
 * stays stable for the lifetime of a post.
 */
class ShortLinkRepository {

	public const TABLE         = 'botcat_short_links';
	public const COLLISION_MAX = 5;

	public function __construct( private readonly ShortCodeGenerator $generator ) {
	}

	public function create_for_post( int $post_id, string $url ): ShortLink {
		$existing = $this->find_for_post( $post_id );
		if ( $existing !== null && $existing->original_url === $url ) {
			return $existing;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$now   = gmdate( 'Y-m-d H:i:s' );

		for ( $attempt = 0; $attempt < self::COLLISION_MAX; $attempt++ ) {
			$code = $this->generator->generate();

			if ( $this->find_by_code( $code ) !== null ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'code'         => $code,
					'post_id'      => $post_id,
					'original_url' => $url,
					'created_at'   => $now,
				),
				array( '%s', '%d', '%s', '%s' )
			);

			return new ShortLink(
				id: (int) $wpdb->insert_id,
				code: $code,
				post_id: $post_id,
				original_url: $url,
				created_at: $now,
			);
		}

		throw new \RuntimeException( 'Could not generate a unique short code after retries' );
	}

	public function find_by_code( string $code ): ?ShortLink {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . self::TABLE . ' WHERE code = %s LIMIT 1', $code ),
			ARRAY_A
		);
		return is_array( $row ) ? ShortLink::from_row( $row ) : null;
	}

	public function find_for_post( int $post_id ): ?ShortLink {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . $wpdb->prefix . self::TABLE . ' WHERE post_id = %d ORDER BY id DESC LIMIT 1',
				$post_id
			),
			ARRAY_A
		);
		return is_array( $row ) ? ShortLink::from_row( $row ) : null;
	}

	/**
	 * @return list<ShortLink>
	 */
	public function list_paged( int $page = 1, int $per_page = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$page  = max( 1, $page );
		$per   = min( 500, max( 1, $per_page ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
				$per,
				( $page - 1 ) * $per
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn( array $row ): ShortLink => ShortLink::from_row( $row ),
			$rows
		);
	}
}
