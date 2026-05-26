<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * Custom auto-update channel for Pro builds.
 *
 * Hooks `pre_set_site_transient_update_plugins` to ask our own
 * manifest endpoint for the latest Pro version, sending the active
 * license key so lapsed sites get "no update".
 *
 * The manifest contract (documented for the release infra):
 *   GET <MANIFEST_URL>?license=<key>&site=<home_url>
 *   200 → { "version": "x.y.z", "package": "<download_url>",
 *           "tested": "x.y", "requires_php": "8.1" }
 *   204 / 404 → no update available
 *
 * Free builds never instantiate this class — the standard WordPress.org
 * channel handles their updates.
 */
class UpdateChannel {

	public const MANIFEST_FILTER = 'botcat_pro_update_manifest_url';

	public function __construct(
		private readonly LicenseRepository $repository,
		private readonly string $plugin_basename,
		private readonly string $current_version
	) {
	}

	public function register(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'inject_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
	}

	public function inject_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$manifest = $this->fetch_manifest();
		if ( $manifest === null ) {
			return $transient;
		}

		if ( ! isset( $manifest['version'] ) || version_compare( (string) $manifest['version'], $this->current_version, '<=' ) ) {
			return $transient;
		}

		$info = (object) array(
			'slug'         => dirname( $this->plugin_basename ),
			'plugin'       => $this->plugin_basename,
			'new_version'  => (string) $manifest['version'],
			'package'      => isset( $manifest['package'] ) ? (string) $manifest['package'] : '',
			'tested'       => isset( $manifest['tested'] ) ? (string) $manifest['tested'] : '',
			'requires_php' => isset( $manifest['requires_php'] ) ? (string) $manifest['requires_php'] : '8.1',
		);

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}

		$transient->response[ $this->plugin_basename ] = $info;
		return $transient;
	}

	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $args->slug !== dirname( $this->plugin_basename ) ) {
			return $result;
		}

		$manifest = $this->fetch_manifest();
		if ( $manifest === null || ! isset( $manifest['version'] ) ) {
			return $result;
		}

		return (object) array(
			'name'          => 'bot-cat Pro',
			'slug'          => dirname( $this->plugin_basename ),
			'version'       => (string) $manifest['version'],
			'requires_php'  => isset( $manifest['requires_php'] ) ? (string) $manifest['requires_php'] : '8.1',
			'tested'        => isset( $manifest['tested'] ) ? (string) $manifest['tested'] : '',
			'download_link' => isset( $manifest['package'] ) ? (string) $manifest['package'] : '',
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function fetch_manifest(): ?array {
		$key = $this->repository->get_key();
		if ( $key === '' ) {
			return null;
		}

		$url = (string) apply_filters( self::MANIFEST_FILTER, 'https://bot-cat.com/updates/manifest.json' );

		$response = wp_remote_get(
			add_query_arg(
				array(
					'license' => $key,
					'site'    => home_url(),
				),
				$url
			),
			array( 'timeout' => 10 )
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return null;
		}

		$payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		return is_array( $payload ) ? $payload : null;
	}
}
