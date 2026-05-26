<?php
/**
 * @package BotCat\Template
 */

declare(strict_types=1);

namespace BotCat\Template;

use BotCat\Flex\FlexTemplate;
use BotCat\Flex\FlexTemplateRepository;
use BotCat\Foundation\Edition;
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

	public const PAGE_SLUG         = 'bot-cat-templates';
	public const OPTION_GROUP      = 'botcat_template_settings_group';
	public const FLEX_OPTION_GROUP = 'botcat_flex_template_settings_group';
	public const TAB_TEXT          = 'text';
	public const TAB_FLEX          = 'flex';

	public function __construct(
		private readonly TemplateRepository $templates,
		private readonly TemplateRenderer $renderer,
		private readonly EligiblePostTypes $eligible,
		private readonly ?Edition $edition = null,
		private readonly ?FlexTemplateRepository $flex_templates = null
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

		if ( $this->flex_templates !== null ) {
			register_setting(
				self::FLEX_OPTION_GROUP,
				FlexTemplateRepository::OPTION,
				array(
					'type'              => 'array',
					'sanitize_callback' => array( $this, 'sanitize_flex' ),
					'default'           => array(),
				)
			);
		}
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

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Message Template', 'bot-cat' ) . '</h1>';

		if ( $this->is_pro_with_flex() ) {
			$tab = $this->current_tab();
			$this->render_tab_nav( $tab );

			if ( $tab === self::TAB_FLEX ) {
				$this->render_flex_form();
				echo '</div>';
				return;
			}
		}

		$this->render_text_form();
		echo '</div>';
	}

	private function render_text_form(): void {
		$active  = $this->templates->get();
		$preview = $this->render_preview( $active );
		$length  = mb_strlen( $preview, 'UTF-8' );
		$status  = $this->templates->status_for_length( $length );

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
	}

	private function render_flex_form(): void {
		$template = $this->flex_templates->get();

		echo '<form method="post" action="options.php">';
		settings_fields( self::FLEX_OPTION_GROUP );

		echo '<table class="form-table" role="presentation"><tbody>';
		$this->flex_select_row( $template );
		$this->flex_text_row( 'default_image_url', __( 'Default image URL', 'bot-cat' ), $template->default_image_url );
		$this->flex_text_row( 'headline', __( 'Headline', 'bot-cat' ), $template->headline );
		$this->flex_text_row( 'body', __( 'Body', 'bot-cat' ), $template->body );
		$this->flex_ctas_row( $template->ctas );
		echo '</tbody></table>';

		submit_button( __( 'Save Flex Template', 'bot-cat' ) );
		echo '</form>';
	}

	private function flex_select_row( FlexTemplate $template ): void {
		echo '<tr><th scope="row"><label for="botcat_flex_hero">' . esc_html__( 'Hero image source', 'bot-cat' ) . '</label></th><td>';
		printf( '<select id="botcat_flex_hero" name="%s[hero_source]">', esc_attr( FlexTemplateRepository::OPTION ) );
		foreach ( array(
			FlexTemplate::HERO_FEATURED => __( 'Featured image', 'bot-cat' ),
			FlexTemplate::HERO_ATTACHED => __( 'First attached image', 'bot-cat' ),
			FlexTemplate::HERO_DEFAULT  => __( 'Default URL only', 'bot-cat' ),
			FlexTemplate::HERO_NONE     => __( 'No hero image', 'bot-cat' ),
		) as $value => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $template->hero_source, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select></td></tr>';
	}

	private function flex_text_row( string $field, string $label, string $value ): void {
		printf(
			'<tr><th scope="row"><label for="botcat_flex_%1$s">%2$s</label></th><td><input type="text" id="botcat_flex_%1$s" name="%3$s[%1$s]" value="%4$s" class="regular-text" /></td></tr>',
			esc_attr( $field ),
			esc_html( $label ),
			esc_attr( FlexTemplateRepository::OPTION ),
			esc_attr( $value )
		);
	}

	/**
	 * @param list<array{label:string,url_template:string}> $ctas
	 */
	private function flex_ctas_row( array $ctas ): void {
		echo '<tr><th scope="row">' . esc_html__( 'Call-to-action buttons', 'bot-cat' ) . '</th><td>';
		for ( $i = 0; $i < FlexTemplate::MAX_CTAS; $i++ ) {
			$cta = $ctas[ $i ] ?? array(
				'label'        => '',
				'url_template' => '',
			);
			printf(
				'<p><input type="text" name="%1$s[ctas][%2$d][label]" value="%3$s" placeholder="%4$s" /> <input type="text" name="%1$s[ctas][%2$d][url_template]" value="%5$s" placeholder="%6$s" class="regular-text" /></p>',
				esc_attr( FlexTemplateRepository::OPTION ),
				(int) $i,
				esc_attr( (string) ( $cta['label'] ?? '' ) ),
				esc_attr__( 'Label', 'bot-cat' ),
				esc_attr( (string) ( $cta['url_template'] ?? '' ) ),
				esc_attr__( 'URL template ({permalink} etc.)', 'bot-cat' )
			);
		}
		echo '<p class="description">' . esc_html__( 'Up to 3 buttons (LINE limit).', 'bot-cat' ) . '</p>';
		echo '</td></tr>';
	}

	public function sanitize_flex( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$ctas = array();
		if ( isset( $input['ctas'] ) && is_array( $input['ctas'] ) ) {
			foreach ( $input['ctas'] as $cta ) {
				if ( ! is_array( $cta ) ) {
					continue;
				}
				$label = isset( $cta['label'] ) ? sanitize_text_field( (string) $cta['label'] ) : '';
				$url   = isset( $cta['url_template'] ) ? sanitize_text_field( (string) $cta['url_template'] ) : '';
				if ( $label === '' && $url === '' ) {
					continue;
				}
				$ctas[] = array(
					'label'        => $label,
					'url_template' => $url,
				);
			}
		}

		return array(
			'hero_source'       => isset( $input['hero_source'] ) ? sanitize_key( (string) $input['hero_source'] ) : FlexTemplate::HERO_FEATURED,
			'default_image_url' => isset( $input['default_image_url'] ) ? esc_url_raw( (string) $input['default_image_url'] ) : '',
			'headline'          => isset( $input['headline'] ) ? sanitize_text_field( (string) $input['headline'] ) : '',
			'body'              => isset( $input['body'] ) ? sanitize_text_field( (string) $input['body'] ) : '',
			'ctas'              => array_slice( $ctas, 0, FlexTemplate::MAX_CTAS ),
		);
	}

	private function is_pro_with_flex(): bool {
		return $this->edition !== null && $this->edition->is_pro() && $this->flex_templates !== null;
	}

	private function current_tab(): string {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['tab'] ) ) : self::TAB_TEXT; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $tab === self::TAB_FLEX ? self::TAB_FLEX : self::TAB_TEXT;
	}

	private function render_tab_nav( string $current ): void {
		$tabs = array(
			self::TAB_TEXT => __( 'Text', 'bot-cat' ),
			self::TAB_FLEX => __( 'Flex Message', 'bot-cat' ),
		);

		echo '<nav class="nav-tab-wrapper">';
		foreach ( $tabs as $slug => $label ) {
			$url = add_query_arg(
				array(
					'page' => self::PAGE_SLUG,
					'tab'  => $slug,
				),
				admin_url( 'admin.php' )
			);
			printf(
				'<a href="%1$s" class="nav-tab%2$s">%3$s</a>',
				esc_url( $url ),
				$current === $slug ? ' nav-tab-active' : '',
				esc_html( $label )
			);
		}
		echo '</nav>';
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
