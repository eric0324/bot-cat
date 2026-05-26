<?php
/**
 * @package BotCat\Template
 */

declare(strict_types=1);

namespace BotCat\Template;

use BotCat\Push\EligiblePostTypes;

/**
 * Admin page for editing the active message template.
 *
 * Server-renders the preview against the latest eligible published
 * post (or a synthetic example when none exists). The character
 * counter colors green / yellow / red and the Save button is disabled
 * (by the form template) when the rendered preview exceeds 5000 chars.
 */
class TemplatePage {

	public const PAGE_SLUG    = 'bot-cat-templates';
	public const OPTION_GROUP = 'botcat_template_settings_group';

	public function __construct(
		private readonly TemplateRepository $templates,
		private readonly TemplateRenderer $renderer,
		private readonly EligiblePostTypes $eligible
	) {
	}

	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			TemplateRepository::OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => TemplateRepository::DEFAULT_TEMPLATE,
			)
		);
	}

	public function sanitize( $input ): string {
		$value = is_string( $input ) ? $input : '';

		$preview = $this->render_preview( $value );
		$error   = $this->templates->length_check( $preview );

		if ( $error !== null ) {
			add_settings_error( TemplateRepository::OPTION, 'too_long', $error );
			return $this->templates->get();
		}

		return $value;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$active  = $this->templates->get();
		$preview = $this->render_preview( $active );
		$length  = mb_strlen( $preview, 'UTF-8' );
		$status  = $this->templates->status_for_length( $length );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Message Template', 'bot-cat' ) . '</h1>';

		echo '<form method="post" action="options.php">';
		settings_fields( self::OPTION_GROUP );

		echo '<table class="form-table" role="presentation"><tbody>';
		$this->render_textarea_row( $active );
		$this->render_token_help_row();
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'Preview', 'bot-cat' ) . '</h2>';
		$this->render_preview_box( $preview, $length, $status );

		submit_button( __( 'Save Template', 'bot-cat' ), 'primary', 'submit', true, $status === TemplateRepository::STATUS_RED ? array( 'disabled' => 'disabled' ) : array() );

		echo '</form>';
		echo '</div>';
	}

	private function render_textarea_row( string $template ): void {
		echo '<tr>';
		echo '<th scope="row"><label for="botcat_template_text">' . esc_html__( 'Template', 'bot-cat' ) . '</label></th>';
		echo '<td>';
		printf(
			'<textarea id="botcat_template_text" name="%1$s" rows="8" class="large-text code">%2$s</textarea>',
			esc_attr( TemplateRepository::OPTION ),
			esc_textarea( $template )
		);
		echo '<p class="description">' . esc_html__( 'Use the tokens below; unknown tokens are kept literal.', 'bot-cat' ) . '</p>';
		echo '</td>';
		echo '</tr>';
	}

	private function render_token_help_row(): void {
		$descriptions = array(
			'title'     => __( 'Post title.', 'bot-cat' ),
			'excerpt'   => __( 'Post excerpt, falling back to the first 100 chars of the content.', 'bot-cat' ),
			'permalink' => __( 'Full URL to the post.', 'bot-cat' ),
			'author'    => __( 'Author display name.', 'bot-cat' ),
			'category'  => __( 'Primary category name.', 'bot-cat' ),
			'date'      => __( 'Publish date, site timezone, format Y-m-d H:i.', 'bot-cat' ),
			'site_name' => __( 'Site name (from General Settings).', 'bot-cat' ),
		);

		echo '<tr>';
		echo '<th scope="row">' . esc_html__( 'Available tokens', 'bot-cat' ) . '</th>';
		echo '<td><dl style="margin: 0;">';

		foreach ( $descriptions as $token => $description ) {
			printf(
				'<dt><code>{%1$s}</code></dt><dd>%2$s</dd>',
				esc_html( $token ),
				esc_html( $description )
			);
		}

		echo '</dl></td>';
		echo '</tr>';
	}

	private function render_preview_box( string $preview, int $length, string $status ): void {
		$style_color = match ( $status ) {
			TemplateRepository::STATUS_RED    => '#b32d2e',
			TemplateRepository::STATUS_YELLOW => '#dba617',
			default                           => '#1f7c34',
		};

		echo '<pre style="background:#f6f7f7;padding:12px;border:1px solid #c3c4c7;white-space:pre-wrap;">';
		echo esc_html( $preview !== '' ? $preview : __( '(Publish a post to see a real preview)', 'bot-cat' ) );
		echo '</pre>';

		printf(
			'<p style="color:%1$s"><strong>%2$d / %3$d</strong> %4$s</p>',
			esc_attr( $style_color ),
			(int) $length,
			(int) TemplateRepository::MAX_RENDERED_LENGTH,
			esc_html__( 'characters', 'bot-cat' )
		);

		if ( $status === TemplateRepository::STATUS_RED ) {
			echo '<div class="notice notice-error inline"><p>' .
				esc_html__( 'LINE will reject messages longer than 5000 characters. Shorten the template or trim the post excerpt.', 'bot-cat' ) .
				'</p></div>';
		}
	}

	private function render_preview( string $template ): string {
		$post = $this->latest_eligible_post();
		if ( $post === null ) {
			return $this->renderer->render( $template, $this->synthetic_post() );
		}

		return $this->renderer->render( $template, $post );
	}

	private function latest_eligible_post(): ?object {
		$types = $this->eligible->get();
		if ( $types === array() ) {
			return null;
		}

		$query = get_posts(
			array(
				'post_type'      => $types,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		if ( ! is_array( $query ) || $query === array() ) {
			return null;
		}

		$post = $query[0];

		return is_object( $post ) ? $post : null;
	}

	private function synthetic_post(): object {
		$post                = new \stdClass();
		$post->ID            = 0;
		$post->post_title    = __( 'Sample post title', 'bot-cat' );
		$post->post_excerpt  = __( 'This is a sample excerpt used to render a preview before any post is published.', 'bot-cat' );
		$post->post_content  = '';
		$post->post_author   = 0;
		$post->post_date_gmt = gmdate( 'Y-m-d H:i:s' );
		return $post;
	}
}
