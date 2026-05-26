<?php
/**
 * @package BotCat\Rules
 */

declare(strict_types=1);

namespace BotCat\Rules;

/**
 * Per-post "Send LINE notification" checkbox.
 *
 * Stored as post meta `_botcat_send_push`:
 *   '1'   → explicitly opted in
 *   '0'   → explicitly opted out
 *   ''    → never touched; fall back to the global default
 *
 * The global default lives in option `botcat_default_push_on`
 * (boolean, defaults to true). Flipping the global default does NOT
 * mutate existing posts — only those without explicit meta.
 */
class PerPostOptOut {

	public const META_KEY       = '_botcat_send_push';
	public const DEFAULT_OPTION = 'botcat_default_push_on';
	public const META_BOX_NONCE = 'botcat_per_post_optout';
	public const META_BOX_FIELD = 'botcat_send_push';

	public function default_for_new_posts(): bool {
		return (bool) get_option( self::DEFAULT_OPTION, true );
	}

	public function is_opted_in( int $post_id ): bool {
		$meta = (string) get_post_meta( $post_id, self::META_KEY, true );

		if ( $meta === '' ) {
			return $this->default_for_new_posts();
		}

		return $meta === '1';
	}

	public function save_for_post( int $post_id, bool $checked ): void {
		update_post_meta( $post_id, self::META_KEY, $checked ? '1' : '0' );
	}

	public function register_meta_box( string $post_type ): void {
		add_meta_box(
			'botcat_per_post_optout',
			__( 'LINE Push', 'bot-cat' ),
			array( $this, 'render_meta_box' ),
			$post_type,
			'side',
			'default'
		);
	}

	public function render_meta_box( object $post ): void {
		$post_id = isset( $post->ID ) ? (int) $post->ID : 0;
		$checked = $post_id > 0 ? $this->is_opted_in( $post_id ) : $this->default_for_new_posts();

		wp_nonce_field( self::META_BOX_NONCE, self::META_BOX_NONCE );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::META_BOX_FIELD ); ?>" value="1" <?php checked( $checked ); ?> />
			<?php esc_html_e( 'Send LINE notification when this post publishes', 'bot-cat' ); ?>
		</label>
		<?php
	}

	public function on_save_post( int $post_id ): void {
		if ( ! isset( $_POST[ self::META_BOX_NONCE ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST[ self::META_BOX_NONCE ] ) ), self::META_BOX_NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$checked = isset( $_POST[ self::META_BOX_FIELD ] ) && (string) $_POST[ self::META_BOX_FIELD ] === '1';
		$this->save_for_post( $post_id, $checked );
	}
}
