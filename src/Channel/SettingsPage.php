<?php
/**
 * @package BotCat\Channel
 */

declare(strict_types=1);

namespace BotCat\Channel;

/**
 * Renders the LINE Channel settings tab and handles save + test-connection
 * submissions via the WordPress Settings API.
 *
 * UI rendering itself is covered by manual acceptance — the saving
 * pipeline (encrypt-on-save, decrypt-on-read) is tested in
 * {@see ChannelSettingsRepositoryTest}.
 */
class SettingsPage {

	public const OPTION_GROUP = 'botcat_channel_settings_group';
	public const SECTION_ID   = 'botcat_channel_section';
	public const NONCE_ACTION = 'botcat_test_connection';

	public function __construct( private readonly ChannelSettingsRepository $repository ) {
	}

	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			ChannelSettingsRepository::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);

		add_settings_section(
			self::SECTION_ID,
			__( 'LINE Channel', 'bot-cat' ),
			array( $this, 'render_intro' ),
			'bot-cat-settings'
		);

		$fields = array(
			'channel_id'     => __( 'Channel ID', 'bot-cat' ),
			'channel_secret' => __( 'Channel Secret', 'bot-cat' ),
			'access_token'   => __( 'Channel Access Token', 'bot-cat' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'botcat_field_' . $key,
				$label,
				array( $this, 'render_field' ),
				'bot-cat-settings',
				self::SECTION_ID,
				array( 'key' => $key )
			);
		}
	}

	/**
	 * @param array<string, mixed> $input
	 * @return array<string, string>
	 */
	public function sanitize( array $input ): array {
		$current = $this->repository->get();

		$channel_id     = isset( $input['channel_id'] ) ? sanitize_text_field( (string) $input['channel_id'] ) : '';
		$channel_secret = isset( $input['channel_secret'] ) && (string) $input['channel_secret'] !== ''
			? sanitize_text_field( (string) $input['channel_secret'] )
			: $current->channel_secret();
		$access_token   = isset( $input['access_token'] ) && (string) $input['access_token'] !== ''
			? sanitize_text_field( (string) $input['access_token'] )
			: $current->access_token();

		if ( $access_token === '' ) {
			add_settings_error(
				ChannelSettingsRepository::OPTION,
				'access_token_required',
				__( 'Channel Access Token is required.', 'bot-cat' )
			);
		}

		$this->repository->save( new ChannelSettings( $channel_id, $channel_secret, $access_token ) );

		return $this->repository->get()->to_array();
	}

	public function render_intro(): void {
		$webhook_url = rest_url( WebhookEndpoint::REST_NAMESPACE . WebhookEndpoint::ROUTE );
		printf(
			'<p>%s</p><p><code>%s</code></p>',
			esc_html__( 'Paste this webhook URL into the LINE Developers console:', 'bot-cat' ),
			esc_url( $webhook_url )
		);
	}

	/**
	 * @param array{key:string} $args
	 */
	public function render_field( array $args ): void {
		$settings = $this->repository->get();
		$key      = $args['key'];

		$value = match ( $key ) {
			'channel_id'     => $settings->channel_id(),
			'channel_secret' => $settings->channel_secret() === '' ? '' : '••••••••',
			'access_token'   => $settings->access_token() === '' ? '' : '••••••••',
			default          => '',
		};

		$type = $key === 'channel_id' ? 'text' : 'password';

		printf(
			'<input type="%1$s" id="botcat_%2$s" name="%3$s[%2$s]" value="%4$s" class="regular-text" autocomplete="off" />',
			esc_attr( $type ),
			esc_attr( $key ),
			esc_attr( ChannelSettingsRepository::OPTION ),
			esc_attr( $value )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$this->render_test_result_banner();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'bot-cat Settings', 'bot-cat' ) . '</h1>';

		echo '<form method="post" action="options.php">';
		settings_fields( self::OPTION_GROUP );
		do_settings_sections( 'bot-cat-settings' );
		submit_button();
		echo '</form>';

		$this->render_test_connection_form();

		echo '</div>';
	}

	private function render_test_result_banner(): void {
		if ( ! isset( $_GET['botcat_test_ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$ok   = (string) $_GET['botcat_test_ok'] === '1';
		$name = isset( $_GET['botcat_test_name'] ) ? sanitize_text_field( rawurldecode( wp_unslash( (string) $_GET['botcat_test_name'] ) ) ) : '';
		$err  = isset( $_GET['botcat_test_err'] ) ? sanitize_text_field( rawurldecode( wp_unslash( (string) $_GET['botcat_test_err'] ) ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $ok ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: LINE Official Account display name. */
						__( 'Connected to %s', 'bot-cat' ),
						$name !== '' ? $name : __( 'your LINE Official Account', 'bot-cat' )
					)
				)
			);
			return;
		}

		printf(
			'<div class="notice notice-error is-dismissible"><p>%1$s %2$s</p></div>',
			esc_html__( 'Connection failed:', 'bot-cat' ),
			esc_html( $err )
		);
	}

	private function render_test_connection_form(): void {
		$action_url = admin_url( 'admin-post.php' );

		echo '<h2>' . esc_html__( 'Test connection', 'bot-cat' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( $action_url ) . '">';
		printf( '<input type="hidden" name="action" value="botcat_test_connection" />' );
		wp_nonce_field( self::NONCE_ACTION );
		submit_button( __( 'Test connection', 'bot-cat' ), 'secondary', 'submit', false );
		echo '</form>';
	}

	public function handle_test_connection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		$tester = new TestConnection();
		$result = $tester->run( $this->repository->get() );

		$redirect = add_query_arg(
			array(
				'page'             => 'bot-cat-settings',
				'botcat_test_ok'   => $result->ok ? '1' : '0',
				'botcat_test_name' => rawurlencode( (string) $result->display_name ),
				'botcat_test_err'  => rawurlencode( (string) $result->error_message ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}
