<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

/**
 * Admin CRUD for tags (Pro only).
 *
 * Single-page form: a list of existing tags on top, and a create/edit
 * form below. Submits to `admin-post.php` so we control nonce and
 * redirect explicitly.
 */
class TagsPage {

	public const PAGE_SLUG     = 'bot-cat-tags';
	public const ACTION_SAVE   = 'botcat_save_tag';
	public const ACTION_DELETE = 'botcat_delete_tag';
	public const NONCE_SAVE    = 'botcat_save_tag';
	public const NONCE_DELETE  = 'botcat_delete_tag';

	public function __construct( private readonly TagRepository $tags ) {
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$editing_id = isset( $_GET['edit'] ) ? (int) $_GET['edit'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$editing    = $editing_id > 0 ? $this->tags->find_by_id( $editing_id ) : null;

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Tags', 'bot-cat' ) . '</h1>';

		$this->render_existing_tags();
		$this->render_form( $editing );

		echo '</div>';
	}

	private function render_existing_tags(): void {
		$tags = $this->tags->list_all();

		echo '<h2>' . esc_html__( 'Existing tags', 'bot-cat' ) . '</h2>';

		if ( $tags === array() ) {
			echo '<p>' . esc_html__( 'No tags yet.', 'bot-cat' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		printf( '<th>%s</th>', esc_html__( 'Display name', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Slug', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Keyword', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Mapped categories', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Actions', 'bot-cat' ) );
		echo '</tr></thead><tbody>';

		foreach ( $tags as $tag ) {
			$edit_url = add_query_arg(
				array(
					'page' => self::PAGE_SLUG,
					'edit' => $tag->id,
				),
				admin_url( 'admin.php' )
			);

			echo '<tr>';
			printf( '<td><strong>%s</strong></td>', esc_html( $tag->display_name ) );
			printf( '<td><code>%s</code></td>', esc_html( $tag->slug ) );
			printf( '<td>%s</td>', esc_html( $tag->keyword ) );
			printf( '<td>%s</td>', esc_html( implode( ', ', array_map( 'strval', $tag->mapped_categories ) ) ) );
			printf(
				'<td><a href="%1$s">%2$s</a> · %3$s</td>',
				esc_url( $edit_url ),
				esc_html__( 'Edit', 'bot-cat' ),
				$this->delete_form_html( $tag )
			);
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private function render_form( ?Tag $editing ): void {
		$action_url = admin_url( 'admin-post.php' );

		echo '<h2>' . esc_html( $editing !== null ? __( 'Edit tag', 'bot-cat' ) : __( 'Create tag', 'bot-cat' ) ) . '</h2>';
		echo '<form method="post" action="' . esc_url( $action_url ) . '">';
		printf( '<input type="hidden" name="action" value="%s" />', esc_attr( self::ACTION_SAVE ) );
		if ( $editing !== null ) {
			printf( '<input type="hidden" name="tag_id" value="%d" />', (int) $editing->id );
		}
		wp_nonce_field( self::NONCE_SAVE );

		echo '<table class="form-table" role="presentation"><tbody>';
		$this->text_row( 'display_name', __( 'Display name', 'bot-cat' ), $editing?->display_name ?? '' );
		$this->text_row( 'slug', __( 'Slug (kebab-case, ASCII)', 'bot-cat' ), $editing?->slug ?? '' );
		$this->text_row( 'keyword', __( 'Subscribe keyword', 'bot-cat' ), $editing?->keyword ?? '' );
		$this->categories_row( $editing?->mapped_categories ?? array() );
		echo '</tbody></table>';

		submit_button( $editing !== null ? __( 'Update tag', 'bot-cat' ) : __( 'Add tag', 'bot-cat' ) );
		echo '</form>';
	}

	private function text_row( string $name, string $label, string $value ): void {
		printf(
			'<tr><th scope="row"><label for="botcat_tag_%1$s">%2$s</label></th><td><input type="text" id="botcat_tag_%1$s" name="%1$s" value="%3$s" class="regular-text" required /></td></tr>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_attr( $value )
		);
	}

	/**
	 * @param list<int> $selected
	 */
	private function categories_row( array $selected ): void {
		$categories = get_categories( array( 'hide_empty' => false ) );

		echo '<tr><th scope="row">' . esc_html__( 'Mapped categories', 'bot-cat' ) . '</th><td>';

		if ( ! is_array( $categories ) || $categories === array() ) {
			echo '<p>' . esc_html__( 'No categories defined yet.', 'bot-cat' ) . '</p>';
		} else {
			foreach ( $categories as $cat ) {
				$id      = isset( $cat->term_id ) ? (int) $cat->term_id : 0;
				$name    = isset( $cat->name ) ? (string) $cat->name : '';
				$checked = in_array( $id, $selected, true );

				printf(
					'<label style="display:block;"><input type="checkbox" name="mapped_categories[]" value="%1$d"%2$s /> %3$s</label>',
					(int) $id,
					$checked ? ' checked' : '',
					esc_html( $name )
				);
			}
		}

		echo '<p class="description">' . esc_html__( 'Posts in any of these categories will push to subscribers of this tag.', 'bot-cat' ) . '</p>';
		echo '</td></tr>';
	}

	private function delete_form_html( Tag $tag ): string {
		ob_start();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline;">';
		printf( '<input type="hidden" name="action" value="%s" />', esc_attr( self::ACTION_DELETE ) );
		printf( '<input type="hidden" name="tag_id" value="%d" />', (int) $tag->id );
		wp_nonce_field( self::NONCE_DELETE );
		echo '<button type="submit" class="button-link delete" onclick="return confirm(\'Delete?\')">' . esc_html__( 'Delete', 'bot-cat' ) . '</button>';
		echo '</form>';
		return (string) ob_get_clean();
	}

	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::NONCE_SAVE );

		$slug              = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( (string) $_POST['slug'] ) ) : '';
		$display_name      = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['display_name'] ) ) : '';
		$keyword           = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['keyword'] ) ) : '';
		$mapped_categories = isset( $_POST['mapped_categories'] ) && is_array( $_POST['mapped_categories'] )
			? array_map( 'intval', wp_unslash( $_POST['mapped_categories'] ) )
			: array();
		$tag_id            = isset( $_POST['tag_id'] ) ? (int) $_POST['tag_id'] : 0;

		if ( $slug === '' || $display_name === '' || $keyword === '' ) {
			$this->redirect( 'invalid' );
		}

		if ( $tag_id > 0 ) {
			$this->tags->update(
				$tag_id,
				array(
					'slug'              => $slug,
					'display_name'      => $display_name,
					'keyword'           => $keyword,
					'mapped_categories' => $mapped_categories,
				)
			);
		} else {
			if ( $this->tags->find_by_slug( $slug ) !== null ) {
				$this->redirect( 'duplicate' );
			}
			$this->tags->create( $slug, $display_name, $keyword, $mapped_categories );
		}

		$this->redirect( 'saved' );
	}

	public function handle_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::NONCE_DELETE );

		$tag_id = isset( $_POST['tag_id'] ) ? (int) $_POST['tag_id'] : 0;
		if ( $tag_id > 0 ) {
			$this->tags->delete( $tag_id );
		}

		$this->redirect( 'deleted' );
	}

	private function redirect( string $status ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => self::PAGE_SLUG,
					'botcat_tag' => $status,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
