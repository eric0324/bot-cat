<?php

class BotCatRoleService {
	private $options;
	private $enable_services;
	private $admin_type_array;
	private $user_type_array;

	public function __construct() {
		$this->options         = [];
		$this->enable_services = [];

		foreach ( SERVICES as $service ) {
			$option = get_option( BOT_CAT_OPTION_PREFIX . $service );

			if ( isset( $option['is_enable'] ) ) {
				$this->options[ $service ] = $option;
				$this->enable_services[]   = $service;
			}
		}

		$this->admin_type_array = [ 'administrator', 'editor', 'author', 'contributor', 'shop_manager' ];
		$this->user_type_array  = [ 'subscriber', 'customer' ];
	}

	/**
	 * @return array
	 */
	public function get_enable_services(): array {
		return $this->enable_services;
	}

	/**
	 * Get can receive post type uuids
	 *
	 * @param $action_name
	 *
	 * @return array
	 */
	public function bot_cat_get_can_receive_post_type_uuids( $action_name ): array {
		$uuids = [];

		foreach ( $this->enable_services as $enable_service ) {
			$admin_roles = [];
			$user_roles  = [];
			foreach ( $this->options[ $enable_service ][ $action_name ] as $role => $need_send ) {
				if ( in_array( $role, $this->admin_type_array, true ) ) {
					$admin_roles[] = $role;
				}
				if ( in_array( $role, $this->user_type_array, true ) ) {
					$user_roles[] = $role;
				}
			}
			$uuids[ $enable_service ]['admin'] = $this->bot_cat_get_uuids_by_role_array( $admin_roles, $enable_service );
			$uuids[ $enable_service ]['user']  = $this->bot_cat_get_uuids_by_role_array( $user_roles, $enable_service );
		}

		return $uuids;
	}

	/**
	 * Get can receive comment type uuids
	 *
	 * @param $action_name
	 *
	 * @return array
	 */
	public function bot_cat_get_can_receive_comment_type_uuids( $action_name ): array {
		$uuids = [];

		foreach ( $this->enable_services as $enable_service ) {
			$admin_roles = [];
			foreach ( $this->options[ $enable_service ][ $action_name ] as $role => $need_send ) {
				if ( in_array( $role, $this->admin_type_array, true ) ) {
					$admin_roles[] = $role;
				}
			}

			$uuids[ $enable_service ]['admin'] = $this->bot_cat_get_uuids_by_role_array( $admin_roles, $enable_service );

		}

		return $uuids;
	}

	/**
	 * Get can receive user type uuids
	 *
	 * @param $action_name
	 *
	 * @return array
	 */
	public function bot_cat_get_can_receive_user_type_uuids( $action_name ): array {
		$uuids = [];

		foreach ( $this->enable_services as $enable_service ) {
			$admin_roles = [];

			foreach ( $this->options[ $enable_service ][ $action_name ] as $role => $need_send ) {
				$admin_roles[] = $role;
			}

			$uuids[ $enable_service ]['admin'] = $this->bot_cat_get_uuids_by_role_array( $admin_roles, $enable_service );
		}

		return $uuids;
	}

	/**
	 * Get can receive WooCommerce product type uuids
	 *
	 * @param $action_name
	 *
	 * @return array
	 */
	public function bot_cat_get_can_receive_wc_product_type_uuids( $action_name ): array {
		$uuids = [];

		foreach ( $this->enable_services as $enable_service ) {
			$admin_roles = [];
			$user_roles  = [];
			foreach ( $this->options[ $enable_service ][ $action_name ] as $role => $need_send ) {
				if ( in_array( $role, $this->admin_type_array, true ) ) {
					$admin_roles[] = $role;
				}
				if ( in_array( $role, $this->user_type_array, true ) ) {
					$user_roles[] = $role;
				}
			}

			$uuids[ $enable_service ]['admin'] = $this->bot_cat_get_uuids_by_role_array( $admin_roles, $enable_service );
			$uuids[ $enable_service ]['user']  = $this->bot_cat_get_uuids_by_role_array( $user_roles, $enable_service );
		}

		return $uuids;
	}

	/**
	 * @param $action_name
	 * @param $order
	 *
	 * @return array
	 */
	public function bot_cat_get_can_receive_wc_order_type_uuids( $action_name, $order ): array {
		$uuids = [];

		foreach ( $this->enable_services as $enable_service ) {
			$admin_roles = [];
			foreach ( $this->options[ $enable_service ][ $action_name ] as $role => $need_send ) {
				if ( in_array( $role, $this->admin_type_array, true ) ) {
					$admin_roles[] = $role;
				}
			}

			$uuid = null;
			if ( $this->options[ $enable_service ][ $action_name ]['user'] ) {
				$uuid = get_user_meta( $order->user_id, BOT_CAT_OPTION_PREFIX . $enable_service . '_uuid', true );

			}

			$uuids[ $enable_service ]['admin'] = $this->bot_cat_get_uuids_by_role_array( $admin_roles, $enable_service );
			$uuids[ $enable_service ]['user']  = $uuid ? [$uuid] : [];
		}

		return $uuids;
	}

	/**
	 * Get uuids by user array
	 *
	 * @param $role_array
	 * @param $message_type
	 *
	 * @return array
	 */
	private function bot_cat_get_uuids_by_role_array( $role_array, $message_type ): array {
		global $wpdb;

		$uuids = [];

		if ( count( $role_array ) === 0 ) {
			return $uuids;
		}

		$user_array = get_users( [ 'role__in' => $role_array ] );

		$ids = [];
		foreach ( $user_array as $user ) {
			$ids[] = $user->ID;
		}

		$in_str_arr = array_fill( 0, count( $ids ), '%s' );
		$in_str     = implode( ',', $in_str_arr );

		$sql = "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = '" . BOT_CAT_OPTION_PREFIX . $message_type . "_uuid' AND user_id IN ($in_str);";

		$sql       = $wpdb->prepare( $sql, $ids );
		$sql_uuids = $wpdb->get_results( $sql );

		foreach ( $sql_uuids as $sql_uuid ) {
			$uuids[] = $sql_uuid->meta_value;
		}

		return $uuids;
	}
}