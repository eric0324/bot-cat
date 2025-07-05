<?php

/**
 * Class BotCatOAuthService
 *
 * This class provides functionality for handling OAuth service integrations for BotCat.
 */

namespace BotCat\Service;


class BotCatOAuthService {
	/**
	 * Generate HTML view for the bot cat OAuth buttons.
	 *
	 * @return string The HTML view for the OAuth buttons.
	 */
	public function bot_cat_oauth_view(): string {
		$user_info = wp_get_current_user();

		if ( ! $user_info ) {
			return '';
		}

		$user_id = $user_info->data->ID;

		$user  = wp_get_current_user();
		$roles = [];
		if ( isset( $user ) ) {
			$roles = $user->roles;
		}

		$html = '<table class="form-table">';

		// LINE
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'line' );

		// Check user role need show OAuth section
		$oauth_show_profile = false;
		if ( isset( $options['oauth_show_profile'] ) && $options['oauth_show_profile'] ) {
			foreach ( $roles as $role ) {
				if ( isset( $options['oauth_show_profile'][ $role ] ) ) {
					$oauth_show_profile = true;
					break;
				}
			}
		}

		// LINE
		if ( isset( $options['is_enable'] ) && $oauth_show_profile ) {
			$line_uuid = get_user_meta( $user_id, BOT_CAT_OPTION_PREFIX . 'line_uuid', true );

			$html .= '<tr><th>LINE</th><td>';

			if ( $line_uuid ) {
				$html .= '<span style="color: green;">✓ ' . __( 'Connected', 'bot-cat' ) . '</span>';
				$html .= '<br><small>' . __( 'User ID:', 'bot-cat' ) . ' ' . esc_html( $line_uuid ) . '</small>';
			} else {
				$html .= '<div style="padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa;">';
				$html .= '<h4>' . __( 'Connect your LINE account', 'bot-cat' ) . '</h4>';
				$html .= '<p>' . __( 'To receive notifications via LINE, you need to:', 'bot-cat' ) . '</p>';
				$html .= '<ol>';
				$html .= '<li>' . __( 'Add your LINE Bot as a friend', 'bot-cat' ) . '</li>';
				$html .= '<li>' . __( 'Send any message to the bot', 'bot-cat' ) . '</li>';
				$html .= '<li>' . __( 'Your account will be automatically connected', 'bot-cat' ) . '</li>';
				$html .= '</ol>';
				$html .= '<p><em>' . __( 'Note: Administrator must configure the LINE Bot Channel Access Token first.', 'bot-cat' ) . '</em></p>';
				$html .= '</div>';
			}

			$html .= "</td></tr>";
		}

		// Telegram
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'telegram' );

		// Check user role need show OAuth section
		$oauth_show_profile = false;
		if ( isset( $options['oauth_show_profile'] ) && $options['oauth_show_profile'] ) {
			foreach ( $roles as $role ) {
				if ( isset( $options['oauth_show_profile'][ $role ] ) ) {
					$oauth_show_profile = true;
					break;
				}
			}
		}

		if ( isset( $options['is_enable'] ) && $oauth_show_profile ) {
			$telegram_uuid = get_user_meta( $user_id, BOT_CAT_OPTION_PREFIX . 'telegram_uuid', true );
			$html          .= '<tr><th>Telegram</th><td>';

			if ( $telegram_uuid ) {
				$html .= __( 'Connected', 'bot-cat' );
			} else {
				$html .= '<p>' . __( 'To connect Telegram, please configure your Telegram Bot credentials in the Telegram settings.', 'bot-cat' ) . '</p>';
			}

			$html .= "</td></tr>";
		}

		// Slack
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'slack' );

		// Check user role need show OAuth section
		$oauth_show_profile = false;
		if ( isset( $options['oauth_show_profile'] ) && $options['oauth_show_profile'] ) {
			foreach ( $roles as $role ) {
				if ( isset( $options['oauth_show_profile'][ $role ] ) ) {
					$oauth_show_profile = true;
					break;
				}
			}
		}

		if ( isset( $options['is_enable'] ) && $oauth_show_profile ) {
			$slack_webhook = isset( $options['webhook_url'] ) ? $options['webhook_url'] : '';
			$html          .= '<tr><th>Slack</th><td>';

			if ( $slack_webhook ) {
				$html .= '<span style="color: green;">✓ ' . __( 'Connected', 'bot-cat' ) . '</span>';
				$html .= '<br><small>' . __( 'Webhook configured', 'bot-cat' ) . '</small>';
				if ( ! empty( $options['channel'] ) ) {
					$html .= '<br><small>' . __( 'Channel:', 'bot-cat' ) . ' ' . esc_html( $options['channel'] ) . '</small>';
				}
			} else {
				$html .= '<div style="padding: 10px; background: #f9f9f9; border-left: 4px solid #0073aa;">';
				$html .= '<h4>' . __( 'Connect Slack workspace', 'bot-cat' ) . '</h4>';
				$html .= '<p>' . __( 'To receive notifications via Slack:', 'bot-cat' ) . '</p>';
				$html .= '<ol>';
				$html .= '<li>' . __( 'Go to Slack settings and configure the webhook URL', 'bot-cat' ) . '</li>';
				$html .= '<li>' . __( 'Choose a channel and bot username', 'bot-cat' ) . '</li>';
				$html .= '<li>' . __( 'Enable Slack notifications', 'bot-cat' ) . '</li>';
				$html .= '</ol>';
				$html .= '<p><em>' . __( 'Note: Administrator must configure the Slack webhook URL first.', 'bot-cat' ) . '</em></p>';
				$html .= '</div>';
			}

			$html .= "</td></tr>";
		}

		$html .= '</table>';

		return $html;
	}
}