<?php

class BotCatOAuthService {
	public function bot_cat_oauth_view(): string {
		$user_info = wp_get_current_user();

		if ( ! $user_info ) {
			return '';
		}

		$user_id = $user_info->data->ID;

		$user_token = get_user_meta( $user_id, BOT_CAT_OPTION_PREFIX . '_token' );

		if ( ! $user_token ) {
			$strings    = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$user_token = $user_id . str_shuffle( $strings );
			update_user_meta( $user_id, BOT_CAT_OPTION_PREFIX . 'token', $user_token );
		} else {
			$user_token = $user_token[0];
		}

		$basic_options  = get_option( BOT_CAT_OPTION_PREFIX . 'basic' );
		$redirect_token = $basic_options['redirect_token'];

		$user  = wp_get_current_user();
		$roles = [];
		if ( isset( $user ) ) {
			$roles = $user->roles;
		}

		$html = '<table class="form-table">';

		// LINE
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'line' );

		// Check user role need show OAuth button
		$oauth_show_profile = false;
		if ( $options['oauth_show_profile'] ) {
			foreach ( $roles as $role ) {
				if ( isset( $options['oauth_show_profile'][ $role ] ) ) {
					$oauth_show_profile = true;
					break;
				}
			}
		}

		// LINE
		if ( isset( $options['is_enable'] ) && $oauth_show_profile ) {
			$url       = BOT_CAT_OFFICIAL_URL . 'auth/' . $redirect_token . '/line?user=' . $user_token;
			$line_uuid = get_user_meta( $user_id, BOT_CAT_OPTION_PREFIX . 'line_uuid' );

			$html .= '<tr><th>LINE</th><td>';

			if ( isset( $line_uuid ) && $line_uuid ) {
				$html .= __( 'Connected', 'bot-cat' );
			} else {
				$html .= '<a href="' . esc_url( $url ) . '">' . __( 'Connect', 'bot-cat' ) . '</a>';
			}

			$html .= "</td></tr>";
		}

		// LINE Notify
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'line_notify' );

		// Check user role need show OAuth button
		$oauth_show_profile = false;
		if ( $options['oauth_show_profile'] ) {
			foreach ( $roles as $role ) {
				if ( isset( $options['oauth_show_profile'][ $role ] ) ) {
					$oauth_show_profile = true;
					break;
				}
			}
		}

		if ( isset( $options['is_enable'] ) && $oauth_show_profile ) {
			$url              = BOT_CAT_OFFICIAL_URL . 'auth/' . $redirect_token . '/line_notify?user=' . $user_token;
			$line_notify_uuid = get_user_meta( $user_id, BOT_CAT_OPTION_PREFIX . 'line_notify_uuid' );

			$html .= '<tr><th>LINE Notify</th><td>';

			if ( isset( $line_notify_uuid ) && $line_notify_uuid ) {
				$html .= __( 'Connected', 'bot-cat' );
			} else {
				$html .= '<a href="' . esc_url( $url ) . '">' . __( 'Connect', 'bot-cat' ) . '</a>';
			}

			$html .= "</td></tr>";
		}

		// Telegram
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'telegram' );

		// Check user role need show OAuth button
		$oauth_show_profile = false;
		if ( $options['oauth_show_profile'] ) {
			foreach ( $roles as $role ) {
				if ( isset( $options['oauth_show_profile'][ $role ] ) ) {
					$oauth_show_profile = true;
					break;
				}
			}
		}

		if ( isset( $options['is_enable'] ) && $oauth_show_profile ) {
			$url           = BOT_CAT_OFFICIAL_URL . 'redirect/' . $redirect_token . '/telegram?user=' . $user_token;
			$telegram_uuid = get_user_meta( $user_id, BOT_CAT_OPTION_PREFIX . 'telegram_uuid' );
			$html          .= '<tr><th>Telegram</th><td>';

			if ( isset( $telegram_uuid ) && $telegram_uuid ) {
				$html .= __( 'Connected', 'bot-cat' );
			} else {
				$html .= '<script async src="https://telegram.org/js/telegram-widget.js?19"
						        data-telegram-login="' . esc_attr( $options['chat_id'] ) . '" 
						        data-size="medium" data-auth-url="' . esc_url( $url ) . '"
						        data-request-access="write"></script>';
			}

			$html .= "</td></tr>";
		}

		$html .= '</table>';

		return $html;
	}
}