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
use BotCat\Push\BatchRunner;
use BotCat\Push\EligiblePostTypes;
use BotCat\Push\MessageBuilder;
use BotCat\Push\MulticastClient;
use BotCat\Push\PostPublishObserver;
use BotCat\Push\PushJobDetailPage;
use BotCat\Push\PushJobRepository;
use BotCat\Push\PushJobRunner;
use BotCat\Push\PushJobScheduler;
use BotCat\Push\PushLogRepository;
use BotCat\Push\PushLogsPage;
use BotCat\Push\RetryPolicy;
use BotCat\Rules\ExcludedCategories;
use BotCat\Rules\PerPostOptOut;
use BotCat\Rules\PushThrottle;
use BotCat\Rules\RulesSettingsTab;
use BotCat\Subscribers\FollowHandler;
use BotCat\Subscribers\LineProfileFetcher;
use BotCat\Subscribers\SubscriberRepository;
use BotCat\Subscribers\SubscribersPage;
use BotCat\Subscribers\UnfollowHandler;
use BotCat\Template\TemplatePage;
use BotCat\Template\TemplateRenderer;
use BotCat\Template\TemplateRepository;
use BotCat\Template\TokenResolver;

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

		$cron = new RetentionCron();
		add_action( Activator::CRON_HOOK, array( $cron, 'run' ) );

		$channel_repo = $this->channel_repository();
		$jobs         = new PushJobRepository();
		$logs         = new PushLogRepository();
		$subscribers  = new SubscriberRepository();

		// Templates.
		$template_repo     = new TemplateRepository();
		$template_renderer = new TemplateRenderer( new TokenResolver() );

		// Push rules.
		$eligible  = new EligiblePostTypes();
		$excluded  = new ExcludedCategories();
		$opt_out   = new PerPostOptOut();
		$throttle  = new PushThrottle();
		$rules_tab = new RulesSettingsTab( $eligible, $excluded );
		add_action( 'admin_init', array( $rules_tab, 'register_settings' ) );

		// Scheduler with throttle.
		$scheduler = new PushJobScheduler( $jobs, $throttle );

		// Template editor page.
		$template_page = new TemplatePage( $template_repo, $template_renderer, $eligible );
		add_action( 'admin_init', array( $template_page, 'register_settings' ) );

		// Channel settings page (with rules tab + send test).
		$settings_page = new ChannelSettingsPage( $channel_repo );
		$settings_page->set_push_scheduler( $scheduler );
		$settings_page->set_rules_tab( $rules_tab );
		add_action( 'admin_init', array( $settings_page, 'register_settings' ) );
		add_action( 'admin_post_botcat_test_connection', array( $settings_page, 'handle_test_connection' ) );
		add_action( 'admin_post_' . ChannelSettingsPage::SEND_TEST_ACTION, array( $settings_page, 'handle_send_test' ) );

		// Per-post opt-out metabox.
		add_action(
			'add_meta_boxes',
			function () use ( $opt_out, $eligible ): void {
				foreach ( $eligible->get() as $post_type ) {
					$opt_out->register_meta_box( $post_type );
				}
			}
		);
		add_action( 'save_post', array( $opt_out, 'on_save_post' ) );

		// Subscribers + push log pages.
		$subscribers_page = new SubscribersPage( $subscribers );
		$push_logs_page   = new PushLogsPage( $jobs, $logs );
		$push_detail_page = new PushJobDetailPage( $jobs, $logs );
		add_action( 'admin_post_' . PushJobDetailPage::RESEND_ACTION, array( $push_detail_page, 'handle_resend' ) );

		// Top-level menu.
		$edition = new Edition();
		$menu    = new AdminMenu(
			$edition,
			array(
				'bot-cat-subscribers' => array( $subscribers_page, 'render' ),
				'bot-cat-templates'   => array( $template_page, 'render' ),
				'bot-cat-logs'        => array( $push_logs_page, 'render' ),
				'bot-cat-settings'    => array( $settings_page, 'render' ),
			)
		);
		add_action( 'admin_menu', array( $menu, 'register' ) );

		// Webhook + subscriber event handlers.
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

		// Publish observer (consults eligibility + excluded categories + per-post opt-out).
		$observer = new PostPublishObserver( $eligible, $scheduler, $excluded, $opt_out );
		add_action( 'transition_post_status', array( $observer, 'on_transition' ), 10, 3 );

		// Push runners.
		$job_runner = new PushJobRunner( $jobs, $logs, $subscribers );
		add_action(
			PushJobScheduler::ACTION_RUN_JOB,
			static function ( $job_id, $custom_user_ids = array() ) use ( $job_runner ): void {
				$ids = is_array( $custom_user_ids ) ? array_map( 'strval', $custom_user_ids ) : array();
				$job_runner->run( (int) $job_id, $ids );
			},
			10,
			2
		);

		$message_builder = new MessageBuilder( $template_repo, $template_renderer );
		$batch_runner    = new BatchRunner(
			$jobs,
			$logs,
			$channel_repo,
			new MulticastClient(),
			new RetryPolicy(),
			$message_builder
		);
		add_action(
			PushJobScheduler::ACTION_RUN_BATCH,
			static function ( $job_id, $log_ids = array(), $attempt = 1 ) use ( $batch_runner ): void {
				$ids = is_array( $log_ids ) ? array_map( 'intval', $log_ids ) : array();
				$batch_runner->run( (int) $job_id, $ids, (int) $attempt );
			},
			10,
			3
		);
	}

	private function channel_repository(): ChannelSettingsRepository {
		$auth_key = defined( 'AUTH_KEY' ) && AUTH_KEY !== '' ? AUTH_KEY : 'bot-cat-fallback-key';
		return new ChannelSettingsRepository( new CredentialEncryptor( $auth_key ) );
	}
}
