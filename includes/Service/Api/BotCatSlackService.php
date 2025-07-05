<?php

/**
 * Class BotCatSlackService
 *
 * This class provides methods for sending text messages to Slack via webhook.
 */

namespace BotCat\Service\Api;

use JsonException;


class BotCatSlackService {

	/**
	 * Send text message to Slack channel via webhook
	 *
	 * @param array $to Array of webhook URLs (not used in current implementation but kept for compatibility)
	 * @param string $message The message to send
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_send_text_message( array $to, string $message ): void {
		// Skip if no message
		if ( empty( $message ) ) {
			return;
		}

		$options = get_option( BOT_CAT_OPTION_PREFIX . 'slack' );

		// Check if webhook URL is configured
		if ( ! isset( $options['webhook_url'] ) || empty( $options['webhook_url'] ) ) {
			error_log( 'BotCat: Slack webhook URL not configured' );
			return;
		}

		$webhook_url = $options['webhook_url'];

		// Prepare payload
		$payload = [
			'text' => $message
		];

		// Add optional channel if configured
		if ( ! empty( $options['channel'] ) ) {
			$payload['channel'] = $options['channel'];
		}

		// Add optional username if configured
		if ( ! empty( $options['username'] ) ) {
			$payload['username'] = $options['username'];
		}

		// Add bot icon
		$payload['icon_emoji'] = ':robot_face:';

		$response = wp_remote_post( $webhook_url, [
			'method'  => 'POST',
			'headers' => [
				'Content-Type' => 'application/json; charset=utf-8',
			],
			'body'    => json_encode( $payload, JSON_THROW_ON_ERROR ),
			'timeout' => 30
		] );

		// Handle response
		if ( is_wp_error( $response ) ) {
			error_log( 'BotCat Slack Webhook Error: ' . $response->get_error_message() );
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			$response_body = wp_remote_retrieve_body( $response );
			error_log( 'BotCat Slack Webhook Error: HTTP ' . $response_code . ' - ' . $response_body );
		}
	}

	/**
	 * Send a formatted message with attachments to Slack
	 *
	 * @param string $title
	 * @param string $message
	 * @param string $color
	 * @param array $fields
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_send_rich_message( string $title, string $message, string $color = 'good', array $fields = [] ): void {
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'slack' );

		// Check if webhook URL is configured
		if ( ! isset( $options['webhook_url'] ) || empty( $options['webhook_url'] ) ) {
			error_log( 'BotCat: Slack webhook URL not configured' );
			return;
		}

		$webhook_url = $options['webhook_url'];

		// Prepare attachment
		$attachment = [
			'title'    => $title,
			'text'     => $message,
			'color'    => $color,
			'fields'   => $fields,
			'footer'   => 'BotCat',
			'ts'       => time()
		];

		// Prepare payload
		$payload = [
			'attachments' => [ $attachment ]
		];

		// Add optional channel if configured
		if ( ! empty( $options['channel'] ) ) {
			$payload['channel'] = $options['channel'];
		}

		// Add optional username if configured
		if ( ! empty( $options['username'] ) ) {
			$payload['username'] = $options['username'];
		}

		// Add bot icon
		$payload['icon_emoji'] = ':robot_face:';

		$response = wp_remote_post( $webhook_url, [
			'method'  => 'POST',
			'headers' => [
				'Content-Type' => 'application/json; charset=utf-8',
			],
			'body'    => json_encode( $payload, JSON_THROW_ON_ERROR ),
			'timeout' => 30
		] );

		// Handle response
		if ( is_wp_error( $response ) ) {
			error_log( 'BotCat Slack Webhook Error: ' . $response->get_error_message() );
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			$response_body = wp_remote_retrieve_body( $response );
			error_log( 'BotCat Slack Webhook Error: HTTP ' . $response_code . ' - ' . $response_body );
		}
	}
}