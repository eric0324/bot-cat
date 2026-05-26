<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

use BotCat\Channel\ChannelSettingsRepository;
use BotCat\Channel\CredentialEncryptor;
use BotCat\Channel\SettingsPage as ChannelSettingsPage;
use BotCat\Channel\WebhookEndpoint;
use BotCat\Subscribers\FollowHandler;
use BotCat\Subscribers\LineProfileFetcher;
use BotCat\Subscribers\SubscriberRepository;
use BotCat\Subscribers\SubscribersPage;
use BotCat\Subscribers\UnfollowHandler;

/**
 * Central bootstrap: instantiates feature classes and wires them to
 * WordPress hooks. Called once per request from bot-cat.php.
 */
final class Plugin {

	private static ?self $instance = null;

	private function __construct( private readonly string $plugin_file ) {
	}

	public static function boot( string $plugin_file ): self {
		if ( self::$instance === null ) {
			self::$instance = new self( $plugin_file );
			self::$instance->register_hooks();
		}

		return self::$instance;
	}

	public static function instance(): ?self {
		return self::$instance;
	}

	public function register_hooks(): void {
		$i18n = new I18n( $this->plugin_file );
		add_action( 'init', array( $i18n, 'load' ) );

		$schema_version = new SchemaVersion( new Schema(), BOT_CAT_VERSION );
		add_action( 'admin_init', array( $schema_version, 'maybe_upgrade' ) );

		$channel_repo  = $this->channel_repository();
		$settings_page = new ChannelSettingsPage( $channel_repo );
		add_action( 'admin_init', array( $settings_page, 'register_settings' ) );
		add_action( 'admin_post_botcat_test_connection', array( $settings_page, 'handle_test_connection' ) );

		$subscribers      = new SubscriberRepository();
		$subscribers_page = new SubscribersPage( $subscribers );

		$edition = new Edition();
		$menu    = new AdminMenu(
			$edition,
			array(
				'bot-cat-subscribers' => array( $subscribers_page, 'render' ),
				'bot-cat-settings'    => array( $settings_page, 'render' ),
			)
		);
		add_action( 'admin_menu', array( $menu, 'register' ) );

		$cron = new RetentionCron();
		add_action( Activator::CRON_HOOK, array( $cron, 'run' ) );

		$profile_fetcher  = new LineProfileFetcher();
		$follow_handler   = new FollowHandler( $subscribers, $channel_repo, $profile_fetcher );
		$unfollow_handler = new UnfollowHandler( $subscribers );

		$webhook = new WebhookEndpoint( $channel_repo, $follow_handler, $unfollow_handler );
		add_action( 'rest_api_init', array( $webhook, 'register' ) );

		add_action(
			FollowHandler::RETRY_HOOK,
			function ( string $line_user_id ) use ( $follow_handler ): void {
				$follow_handler->handle( $line_user_id, time() );
			}
		);
	}

	private function channel_repository(): ChannelSettingsRepository {
		$auth_key = defined( 'AUTH_KEY' ) && AUTH_KEY !== '' ? AUTH_KEY : 'bot-cat-fallback-key';
		return new ChannelSettingsRepository( new CredentialEncryptor( $auth_key ) );
	}
}
