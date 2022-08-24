<?php

class BotCatMessageApi {
	private $botCatBasicAuthService;

	public function __construct() {
		$this->botCatBasicAuthService = new BotCatAuthService();
	}

	public function register_rest_route(): void {
		register_rest_route( BOT_CAT_REST_NAMESPACE_PREFIX, '/message/admin', [
			'methods'             => 'POST',
			'callback'            => [ &$this, 'store_admin_message' ],
			'permission_callback' => '__return_true'
		] );

		register_rest_route( BOT_CAT_REST_NAMESPACE_PREFIX, '/message/client', [
			'methods'             => 'POST',
			'callback'            => [ &$this, 'store_client_message' ],
			'permission_callback' => '__return_true'
		] );
	}

	/**
	 * @param $request
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function store_admin_message( $request ): void {
		$can_access = $this->botCatBasicAuthService->check_key( $request );

		if ( ! $can_access ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		update_option( BOT_CAT_OPTION_PREFIX . 'admin_message', $request['messages'] );
	}

	/**
	 * @param $request
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function store_client_message( $request ): void {
		$can_access = $this->botCatBasicAuthService->check_key( $request );

		if ( ! $can_access ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		update_option( BOT_CAT_OPTION_PREFIX . 'client_message', $request['messages'] );
	}

}