<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

/**
 * CRUD for `botcat_tags` rows.
 *
 * The `mapped_categories` column is stored as a JSON array of int term
 * IDs and re-decoded on read.
 */
class TagRepository {

	public const TABLE = 'botcat_tags';
	public const JOIN  = 'botcat_subscriber_tags';

	/**
	 * @param list<int|string> $mapped_categories
	 */
	public function create( string $slug, string $display_name, string $keyword, array $mapped_categories ): int {
		global $wpdb;
		$now = gmdate( 'Y-m-d H:i:s' );

		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'slug'              => $slug,
				'display_name'      => $display_name,
				'keyword'           => $keyword,
				'mapped_categories' => $this->encode_categories( $mapped_categories ),
				'created_at'        => $now,
				'updated_at'        => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	public function find_by_id( int $id ): ?Tag {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . self::TABLE . ' WHERE id = %d LIMIT 1', $id ),
			ARRAY_A
		);
		return is_array( $row ) ? Tag::from_row( $row ) : null;
	}

	public function find_by_slug( string $slug ): ?Tag {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . self::TABLE . ' WHERE slug = %s LIMIT 1', $slug ),
			ARRAY_A
		);
		return is_array( $row ) ? Tag::from_row( $row ) : null;
	}

	public function find_by_keyword( string $keyword ): ?Tag {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . self::TABLE . ' WHERE keyword = %s LIMIT 1', $keyword ),
			ARRAY_A
		);
		return is_array( $row ) ? Tag::from_row( $row ) : null;
	}

	/**
	 * @return list<Tag>
	 */
	public function list_all(): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			'SELECT * FROM ' . $wpdb->prefix . self::TABLE . ' ORDER BY display_name',
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return array();
		}
		return array_map( static fn( array $row ): Tag => Tag::from_row( $row ), $rows );
	}

	/**
	 * @param list<int> $category_ids
	 * @return list<Tag>
	 */
	public function find_for_categories( array $category_ids ): array {
		if ( $category_ids === array() ) {
			return array();
		}

		$matching = array();
		foreach ( $this->list_all() as $tag ) {
			if ( array_intersect( $tag->mapped_categories, $category_ids ) !== array() ) {
				$matching[] = $tag;
			}
		}
		return $matching;
	}

	/**
	 * @param array{slug?:string, display_name?:string, keyword?:string, mapped_categories?:list<int>} $changes
	 */
	public function update( int $id, array $changes ): void {
		global $wpdb;
		$data    = array( 'updated_at' => gmdate( 'Y-m-d H:i:s' ) );
		$formats = array( '%s' );

		if ( isset( $changes['slug'] ) ) {
			$data['slug'] = (string) $changes['slug'];
			$formats[]    = '%s';
		}
		if ( isset( $changes['display_name'] ) ) {
			$data['display_name'] = (string) $changes['display_name'];
			$formats[]            = '%s';
		}
		if ( isset( $changes['keyword'] ) ) {
			$data['keyword'] = (string) $changes['keyword'];
			$formats[]       = '%s';
		}
		if ( isset( $changes['mapped_categories'] ) ) {
			$data['mapped_categories'] = $this->encode_categories( $changes['mapped_categories'] );
			$formats[]                 = '%s';
		}

		$wpdb->update(
			$wpdb->prefix . self::TABLE,
			$data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);
	}

	public function delete( int $id ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . $wpdb->prefix . self::JOIN . ' WHERE tag_id = %d',
				$id
			)
		);

		$wpdb->delete(
			$wpdb->prefix . self::TABLE,
			array( 'id' => $id ),
			array( '%d' )
		);
	}

	/**
	 * @param array<int|string> $categories
	 */
	private function encode_categories( array $categories ): string {
		$ints = array_values( array_unique( array_map( 'intval', $categories ) ) );
		return (string) wp_json_encode( $ints );
	}
}
