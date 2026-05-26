<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

use BotCat\Channel\ChannelSettingsRepository;
use BotCat\Channel\CredentialEncryptor;
use BotCat\Channel\ReplyClient;
use BotCat\Channel\SettingsPage as ChannelSettingsPage;
use BotCat\Channel\WebhookEndpoint;
use BotCat\Flex\FlexMessageBuilder;
use BotCat\Flex\FlexTemplateRepository;
use BotCat\Flex\HeroImageResolver;
use BotCat\Analytics\CsvExporter;
use BotCat\Analytics\DashboardKpiCalculator;
use BotCat\Analytics\DashboardPage;
use BotCat\Analytics\PostPerformanceQuery;
use BotCat\Analytics\SubscriberGrowthQuery;
use BotCat\Push\BatchRunner;
use BotCat\Push\EligiblePostTypes;
use BotCat\Push\MessageBuilder;
use BotCat\Push\MulticastClient;
use BotCat\Push\PostPublishObserver;
use BotCat\Push\PushJob;
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
use BotCat\Tags\AudienceResolver;
use BotCat\Tags\MessageEventHandler;
use BotCat\Tags\SubscriberTagRepository;
use BotCat\Tags\TagCommandHandler;
use BotCat\Tags\TagCommandParser;
use BotCat\Tags\TagRepository;
use BotCat\Tags\TagsPage;
use BotCat\License\LicenseActivationHandler;
use BotCat\License\LicenseGate;
use BotCat\License\LicensePage;
use BotCat\License\LicenseRepository;
use BotCat\License\LicenseRevalidationCron;
use BotCat\License\LicenseStatusEvaluator;
use BotCat\License\PolarClient;
use BotCat\License\PolarWebhookEndpoint;
use BotCat\License\UpdateChannel;
use BotCat\Shortener\ClickLogRepository;
use BotCat\Shortener\ClickRedirectHandler;
use BotCat\Shortener\LinkRewriter;
use BotCat\Shortener\RewriteRuleRegistrar;
use BotCat\Shortener\ShortCodeGenerator;
use BotCat\Shortener\ShortLinkRepository;
use BotCat\Shortener\ShortLinksPage;
use BotCat\Shortener\SubscriberTokenSigner;
use BotCat\Tags\WelcomeMessageBuilder;
use BotCat\Template\TemplatePage;
use BotCat\Template\TemplateRenderer;
use BotCat\Template\TemplateRepository;
use BotCat\Template\TokenResolver;
use Throwable;

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

		$channel_repo    = $this->channel_repository();
		$jobs            = new PushJobRepository();
		$logs            = new PushLogRepository();
		$subscribers     = new SubscriberRepository();
		$tags            = new TagRepository();
		$subscriber_tags = new SubscriberTagRepository();

		// Templates.
		$template_repo     = new TemplateRepository();
		$template_renderer = new TemplateRenderer( new TokenResolver() );

		// Pro Flex template.
		$flex_template_repo = new FlexTemplateRepository();
		$flex_builder       = new FlexMessageBuilder( $template_renderer, new HeroImageResolver() );

		// License (must come before Edition so the filter is registered).
		$license_repo      = new LicenseRepository();
		$license_evaluator = new LicenseStatusEvaluator();
		$license_gate      = new LicenseGate( $license_repo, $license_evaluator );
		$polar_client      = new PolarClient();
		add_filter( 'botcat_is_pro', array( $license_gate, 'filter_is_pro' ) );

		// Edition + rules.
		$edition   = new Edition();
		$eligible  = new EligiblePostTypes();
		$excluded  = new ExcludedCategories();
		$opt_out   = new PerPostOptOut();
		$throttle  = new PushThrottle();
		$rules_tab = new RulesSettingsTab( $eligible, $excluded );
		add_action( 'admin_init', array( $rules_tab, 'register_settings' ) );

		// Push scheduler with throttle.
		$scheduler = new PushJobScheduler( $jobs, $throttle );

		// Template editor page (with Pro Flex tab).
		$template_page = new TemplatePage( $template_repo, $template_renderer, $eligible, $edition, $flex_template_repo );
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

		// Pro: Tags admin page.
		$tags_page = new TagsPage( $tags );
		add_action( 'admin_post_' . TagsPage::ACTION_SAVE, array( $tags_page, 'handle_save' ) );
		add_action( 'admin_post_' . TagsPage::ACTION_DELETE, array( $tags_page, 'handle_delete' ) );

		// Pro: short link infrastructure.
		$short_links   = new ShortLinkRepository( new ShortCodeGenerator() );
		$click_log     = new ClickLogRepository();
		$token_signer  = new SubscriberTokenSigner( defined( 'AUTH_KEY' ) && AUTH_KEY !== '' ? AUTH_KEY : 'bot-cat-fallback-key' );
		$short_base    = function_exists( 'home_url' ) ? (string) home_url( '/l/' ) : '/l/';
		$link_rewriter = new LinkRewriter( $short_links, function_exists( 'home_url' ) ? (string) home_url() : '', $short_base );

		if ( $edition->is_pro() ) {
			$redirect_handler  = new ClickRedirectHandler( $short_links, $click_log, $token_signer, defined( 'AUTH_KEY' ) && AUTH_KEY !== '' ? AUTH_KEY : 'bot-cat-fallback-salt' );
			$rewrite_registrar = new RewriteRuleRegistrar( $redirect_handler );
			add_action( 'init', array( $rewrite_registrar, 'register_rewrite' ) );
			add_filter( 'query_vars', array( $rewrite_registrar, 'register_query_var' ) );
			add_action( 'template_redirect', array( $rewrite_registrar, 'dispatch' ) );
		}

		$short_links_page = new ShortLinksPage( $short_links, $click_log, $short_base );

		// Pro: analytics dashboard.
		$dashboard_page = new DashboardPage(
			new DashboardKpiCalculator(),
			new PostPerformanceQuery(),
			new SubscriberGrowthQuery(),
			new CsvExporter()
		);
		add_action( 'admin_post_' . DashboardPage::CSV_ACTION, array( $dashboard_page, 'handle_csv' ) );

		// Top-level menu.
		$default_landing = $edition->is_pro()
			? array( $dashboard_page, 'render' )
			: array( $subscribers_page, 'render' );

		$menu_renderers = array(
			'bot-cat'             => $default_landing,
			'bot-cat-subscribers' => array( $subscribers_page, 'render' ),
			'bot-cat-templates'   => array( $template_page, 'render' ),
			'bot-cat-logs'        => array( $push_logs_page, 'render' ),
			'bot-cat-settings'    => array( $settings_page, 'render' ),
		);
		if ( $edition->is_pro() ) {
			$menu_renderers['bot-cat-tags']        = array( $tags_page, 'render' );
			$menu_renderers['bot-cat-short-links'] = array( $short_links_page, 'render' );

			$license_page       = new LicensePage( $license_repo, $license_evaluator );
			$activation_handler = new LicenseActivationHandler( $license_repo, $polar_client );
			$polar_webhook      = new PolarWebhookEndpoint( $license_repo );
			$revalidation_cron  = new LicenseRevalidationCron( $license_repo, $polar_client );

			$menu_renderers['bot-cat-license'] = array( $license_page, 'render' );

			add_action( 'admin_post_' . LicensePage::ACTION_ACTIVATE, array( $activation_handler, 'handle_activate' ) );
			add_action( 'admin_post_' . LicensePage::ACTION_DEACTIVATE, array( $activation_handler, 'handle_deactivate' ) );
			add_action( 'rest_api_init', array( $polar_webhook, 'register' ) );
			add_action( LicenseRevalidationCron::HOOK, array( $revalidation_cron, 'run' ) );

			// Schedule the daily revalidation if not already.
			if ( function_exists( 'wp_next_scheduled' ) && ! wp_next_scheduled( LicenseRevalidationCron::HOOK ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', LicenseRevalidationCron::HOOK );
			}

			// Pro auto-update channel.
			$update_channel = new UpdateChannel( $license_repo, plugin_basename( $this->plugin_file ), BOT_CAT_VERSION );
			$update_channel->register();
		}
		$menu = new AdminMenu( $edition, $menu_renderers );
		add_action( 'admin_menu', array( $menu, 'register' ) );

		// Webhook + subscriber + tag event handlers.
		$profile_fetcher  = new LineProfileFetcher();
		$reply_client     = new ReplyClient();
		$welcome          = new WelcomeMessageBuilder( $tags );
		$follow_handler   = new FollowHandler(
			$subscribers,
			$channel_repo,
			$profile_fetcher,
			$edition->is_pro() ? $welcome : null,
			$edition->is_pro() ? $reply_client : null
		);
		$unfollow_handler = new UnfollowHandler( $subscribers );

		$message_handler = null;
		if ( $edition->is_pro() ) {
			$command_handler = new TagCommandHandler( $tags, $subscriber_tags, $subscribers );
			$message_handler = new MessageEventHandler(
				new TagCommandParser(),
				$command_handler,
				$channel_repo,
				$reply_client
			);
		}

		$webhook = new WebhookEndpoint( $channel_repo, $follow_handler, $unfollow_handler, $message_handler );
		add_action( 'rest_api_init', array( $webhook, 'register' ) );

		add_action(
			FollowHandler::RETRY_HOOK,
			function ( string $line_user_id ) use ( $follow_handler ): void {
				$follow_handler->handle( $line_user_id, time() );
			}
		);

		// Publish observer.
		$observer = new PostPublishObserver( $eligible, $scheduler, $excluded, $opt_out );
		add_action( 'transition_post_status', array( $observer, 'on_transition' ), 10, 3 );

		// Pro: tag-aware audience.
		$audience = $edition->is_pro()
			? new AudienceResolver( $subscribers, $tags, $subscriber_tags )
			: null;

		// Push runners.
		$job_runner = new PushJobRunner( $jobs, $logs, $subscribers, $audience );
		add_action(
			PushJobScheduler::ACTION_RUN_JOB,
			static function ( $job_id, $custom_user_ids = array() ) use ( $job_runner ): void {
				$ids = is_array( $custom_user_ids ) ? array_map( 'strval', $custom_user_ids ) : array();
				$job_runner->run( (int) $job_id, $ids );
			},
			10,
			2
		);

		$message_builder = new MessageBuilder(
			$template_repo,
			$template_renderer,
			$edition->is_pro() ? $flex_template_repo : null,
			$edition->is_pro() ? $flex_builder : null,
			$edition,
			$edition->is_pro() ? $link_rewriter : null
		);

		// Surface Flex render failures on the Push Job detail page.
		add_action(
			'botcat_flex_render_failed',
			static function ( PushJob $job, Throwable $exception ) use ( $jobs ): void {
				$jobs->set_last_error(
					$job->id,
					sprintf(
						/* translators: %s: underlying exception message. */
						__( 'Flex template failed — sent as text: %s', 'bot-cat' ),
						$exception->getMessage()
					)
				);
			},
			10,
			2
		);

		$batch_runner = new BatchRunner(
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
