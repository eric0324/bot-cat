<?php
/**
 * @package BotCat\Analytics
 */

declare(strict_types=1);

namespace BotCat\Analytics;

/**
 * Pro-edition Dashboard top page.
 *
 * Server-rendered KPI cards + simple HTML table for per-post
 * performance. Chart.js bundling for the line charts is left for
 * a follow-up — the data layer is in place and exposed via a
 * minimal JSON endpoint the front-end can hit later.
 */
class DashboardPage {

	public const PAGE_SLUG      = 'bot-cat';
	public const USER_META_DAYS = 'botcat_dashboard_days';
	public const CSV_ACTION     = 'botcat_dashboard_csv';
	public const CSV_NONCE      = 'botcat_dashboard_csv';

	public function __construct(
		private readonly DashboardKpiCalculator $kpis,
		private readonly PostPerformanceQuery $posts,
		private readonly SubscriberGrowthQuery $growth,
		private readonly CsvExporter $csv
	) {
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$window = new AnalyticsWindow( $this->current_days() );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'bot-cat Dashboard', 'bot-cat' ) . '</h1>';

		$this->render_window_selector( $window );
		$this->render_kpis( $window );
		$this->render_growth( $window );
		$this->render_post_table( $window );

		echo '</div>';
	}

	public function current_days(): int {
		if ( isset( $_GET['window'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$candidate = (int) wp_unslash( $_GET['window'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $candidate, AnalyticsWindow::ALLOWED_DAYS, true ) ) {
				update_user_meta( get_current_user_id(), self::USER_META_DAYS, $candidate );
				return $candidate;
			}
		}

		$stored = (int) get_user_meta( get_current_user_id(), self::USER_META_DAYS, true );
		return in_array( $stored, AnalyticsWindow::ALLOWED_DAYS, true ) ? $stored : AnalyticsWindow::DEFAULT_DAYS;
	}

	public function handle_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::CSV_NONCE );

		$window = new AnalyticsWindow( $this->current_days() );
		$rows   = $this->posts->run( $window );

		$csv_rows = array_map(
			static fn( array $r ): array => array(
				$r['post_title'],
				$r['triggered_at'],
				$r['delivered'],
				$r['failed'],
				$r['clicks'],
				$r['ctr'] . '%',
			),
			$rows
		);

		$this->csv->send(
			'bot-cat-performance-' . gmdate( 'Y-m-d' ) . '.csv',
			array(
				__( 'Post', 'bot-cat' ),
				__( 'Triggered at', 'bot-cat' ),
				__( 'Delivered', 'bot-cat' ),
				__( 'Failed', 'bot-cat' ),
				__( 'Clicks', 'bot-cat' ),
				__( 'CTR', 'bot-cat' ),
			),
			$csv_rows
		);
		exit;
	}

	private function render_window_selector( AnalyticsWindow $window ): void {
		echo '<p>';
		foreach ( AnalyticsWindow::ALLOWED_DAYS as $days ) {
			$url = add_query_arg(
				array(
					'page'   => self::PAGE_SLUG,
					'window' => $days,
				),
				admin_url( 'admin.php' )
			);
			printf(
				'<a href="%1$s" class="button%2$s">%3$s</a> ',
				esc_url( $url ),
				$days === $window->days ? ' button-primary' : '',
				esc_html(
					sprintf(
						/* translators: %d: window in days. */
						__( 'Last %d days', 'bot-cat' ),
						$days
					)
				)
			);
		}
		echo '</p>';
	}

	private function render_kpis( AnalyticsWindow $window ): void {
		$active     = $this->kpis->active_subscribers();
		$deliveries = $this->kpis->deliveries_in_window( $window );
		$ctr        = $this->kpis->ctr_percent( $window );
		$latency    = $this->kpis->median_click_latency_seconds( $window );

		echo '<div class="botcat-kpis" style="display:flex;gap:16px;flex-wrap:wrap;">';
		$this->kpi_card( __( 'Active subscribers', 'bot-cat' ), (string) $active );
		$this->kpi_card( __( 'Deliveries', 'bot-cat' ), (string) $deliveries );
		$this->kpi_card( __( 'CTR', 'bot-cat' ), $ctr . '%' );
		$this->kpi_card( __( 'Median click latency', 'bot-cat' ), $latency !== null ? $latency . 's' : '—' );
		echo '</div>';
	}

	private function kpi_card( string $label, string $value ): void {
		printf(
			'<div style="border:1px solid #c3c4c7;padding:12px;border-radius:4px;background:#fff;min-width:160px;"><div style="font-size:13px;color:#666;">%1$s</div><div style="font-size:28px;font-weight:600;">%2$s</div></div>',
			esc_html( $label ),
			esc_html( $value )
		);
	}

	private function render_growth( AnalyticsWindow $window ): void {
		$series = $this->growth->run( $window );
		if ( $series === array() ) {
			return;
		}

		echo '<h2>' . esc_html__( 'Subscriber growth', 'bot-cat' ) . '</h2>';
		echo '<table class="widefat striped" style="max-width:600px;"><thead><tr>';
		printf( '<th>%s</th><th>%s</th>', esc_html__( 'Date', 'bot-cat' ), esc_html__( 'Active', 'bot-cat' ) );
		echo '</tr></thead><tbody>';
		foreach ( $series as $row ) {
			echo '<tr>';
			printf( '<td>%s</td>', esc_html( $row['date'] ) );
			printf( '<td>%d</td>', (int) $row['active'] );
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	private function render_post_table( AnalyticsWindow $window ): void {
		$rows = $this->posts->run( $window );

		echo '<h2>' . esc_html__( 'Per-post performance', 'bot-cat' ) . '</h2>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-bottom:1em;">';
		printf( '<input type="hidden" name="action" value="%s" />', esc_attr( self::CSV_ACTION ) );
		wp_nonce_field( self::CSV_NONCE );
		submit_button( __( 'Download CSV', 'bot-cat' ), 'secondary', 'submit', false );
		echo '</form>';

		if ( $rows === array() ) {
			echo '<p>' . esc_html__( 'No pushes in this window yet.', 'bot-cat' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		printf( '<th>%s</th>', esc_html__( 'Post', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Triggered', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Delivered', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Failed', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Clicks', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'CTR', 'bot-cat' ) );
		echo '</tr></thead><tbody>';
		foreach ( $rows as $r ) {
			echo '<tr>';
			printf( '<td>%s</td>', esc_html( $r['post_title'] !== '' ? $r['post_title'] : '#' . $r['post_id'] ) );
			printf( '<td>%s</td>', esc_html( $r['triggered_at'] ) );
			printf( '<td>%d</td>', (int) $r['delivered'] );
			printf( '<td>%d</td>', (int) $r['failed'] );
			printf( '<td>%d</td>', (int) $r['clicks'] );
			printf( '<td>%s%%</td>', esc_html( (string) $r['ctr'] ) );
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
}
