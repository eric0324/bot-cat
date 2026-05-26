<?php
/**
 * @package BotCat\Rules
 */

declare(strict_types=1);

namespace BotCat\Rules;

use BotCat\Push\EligiblePostTypes;

/**
 * Renders and persists the "Push rules" tab of the Settings page.
 *
 * Owns four options:
 *   botcat_eligible_post_types
 *   botcat_excluded_categories
 *   botcat_push_throttle_minutes
 *   botcat_default_push_on
 */
class RulesSettingsTab {

	public const OPTION_GROUP = 'botcat_push_rules_group';

	public function __construct(
		private readonly EligiblePostTypes $eligible,
		private readonly ExcludedCategories $excluded
	) {
	}

	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			EligiblePostTypes::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_post_types' ),
				'default'           => EligiblePostTypes::DEFAULT,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			ExcludedCategories::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_term_ids' ),
				'default'           => array(),
			)
		);

		register_setting(
			self::OPTION_GROUP,
			PushThrottle::OPTION_INTERVAL_MIN,
			array(
				'type'              => 'integer',
				'sanitize_callback' => static fn( $v ) => max( 0, (int) $v ),
				'default'           => 0,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			PerPostOptOut::DEFAULT_OPTION,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => static fn( $v ) => (bool) $v,
				'default'           => true,
			)
		);
	}

	/**
	 * @param mixed $input
	 * @return list<string>
	 */
	public function sanitize_post_types( $input ): array {
		if ( ! is_array( $input ) ) {
			return EligiblePostTypes::DEFAULT;
		}

		$candidates = array_map( 'sanitize_key', $input );
		$candidates = array_filter(
			$candidates,
			static fn( string $key ): bool => $key !== '' && ! in_array( $key, array( 'page', 'attachment' ), true )
		);

		return $candidates === array() ? EligiblePostTypes::DEFAULT : array_values( array_unique( $candidates ) );
	}

	/**
	 * @param mixed $input
	 * @return list<int>
	 */
	public function sanitize_term_ids( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		return array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $input ),
					static fn( int $id ): bool => $id > 0
				)
			)
		);
	}

	public function render(): void {
		echo '<form method="post" action="options.php">';
		settings_fields( self::OPTION_GROUP );
		echo '<table class="form-table" role="presentation"><tbody>';

		$this->render_post_types_row();
		$this->render_excluded_categories_row();
		$this->render_throttle_row();
		$this->render_default_opt_in_row();

		echo '</tbody></table>';
		submit_button();
		echo '</form>';
	}

	private function render_post_types_row(): void {
		$selected  = $this->eligible->get();
		$available = $this->available_post_types();

		echo '<tr><th scope="row">' . esc_html__( 'Eligible post types', 'bot-cat' ) . '</th><td>';
		foreach ( $available as $slug => $label ) {
			$disabled = in_array( $slug, array( 'page', 'attachment' ), true );
			$checked  = in_array( $slug, $selected, true );

			printf(
				'<label style="display:block;"><input type="checkbox" name="%1$s[]" value="%2$s"%3$s%4$s /> %5$s <code>%2$s</code></label>',
				esc_attr( EligiblePostTypes::OPTION ),
				esc_attr( $slug ),
				$disabled ? ' disabled' : '',
				$checked && ! $disabled ? ' checked' : '',
				esc_html( $label )
			);
		}
		echo '<p class="description">' . esc_html__( 'page and attachment cannot be selected.', 'bot-cat' ) . '</p>';
		echo '</td></tr>';
	}

	private function render_excluded_categories_row(): void {
		$selected   = $this->excluded->get();
		$categories = get_categories(
			array(
				'hide_empty' => false,
				'orderby'    => 'name',
			)
		);

		echo '<tr><th scope="row">' . esc_html__( 'Excluded categories', 'bot-cat' ) . '</th><td>';

		if ( ! is_array( $categories ) || $categories === array() ) {
			echo '<p>' . esc_html__( 'No categories defined yet.', 'bot-cat' ) . '</p>';
		} else {
			foreach ( $categories as $cat ) {
				$id      = isset( $cat->term_id ) ? (int) $cat->term_id : 0;
				$name    = isset( $cat->name ) ? (string) $cat->name : '';
				$checked = in_array( $id, $selected, true );

				printf(
					'<label style="display:block;"><input type="checkbox" name="%1$s[]" value="%2$d"%3$s /> %4$s</label>',
					esc_attr( ExcludedCategories::OPTION ),
					(int) $id,
					$checked ? ' checked' : '',
					esc_html( $name )
				);
			}
		}

		echo '<p class="description">' . esc_html__( 'Posts assigned to any selected category will NOT push.', 'bot-cat' ) . '</p>';
		echo '</td></tr>';
	}

	private function render_throttle_row(): void {
		$current = (int) get_option( PushThrottle::OPTION_INTERVAL_MIN, 0 );

		echo '<tr><th scope="row"><label for="' . esc_attr( PushThrottle::OPTION_INTERVAL_MIN ) . '">' . esc_html__( 'Min minutes between pushes', 'bot-cat' ) . '</label></th><td>';
		printf(
			'<input type="number" min="0" id="%1$s" name="%1$s" value="%2$d" class="small-text" />',
			esc_attr( PushThrottle::OPTION_INTERVAL_MIN ),
			(int) $current
		);
		echo '<p class="description">' . esc_html__( '0 = no throttle. Bursts publish all queue at once but the channel waits this many minutes between consecutive multicast jobs.', 'bot-cat' ) . '</p>';
		echo '</td></tr>';
	}

	private function render_default_opt_in_row(): void {
		$default_on = (bool) get_option( PerPostOptOut::DEFAULT_OPTION, true );

		echo '<tr><th scope="row">' . esc_html__( 'Default for new posts', 'bot-cat' ) . '</th><td>';
		printf(
			'<label><input type="checkbox" name="%1$s" value="1"%2$s /> %3$s</label>',
			esc_attr( PerPostOptOut::DEFAULT_OPTION ),
			$default_on ? ' checked' : '',
			esc_html__( 'New posts default to "Send LINE notification" being ON.', 'bot-cat' )
		);
		echo '</td></tr>';
	}

	/**
	 * @return array<string, string>
	 */
	private function available_post_types(): array {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		if ( ! is_array( $types ) ) {
			return array( 'post' => 'Post' );
		}

		$map = array();
		foreach ( $types as $slug => $object ) {
			$map[ (string) $slug ] = isset( $object->label ) ? (string) $object->label : (string) $slug;
		}

		return $map;
	}
}
