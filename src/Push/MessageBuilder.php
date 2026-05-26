<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Produces the LINE message payload for a given push job.
 *
 * Default (W2) implementation emits a plain text message with the post
 * title and permalink. W3 will swap in a Templates-based builder via the
 * `botcat_message_builder` filter without disturbing this contract.
 */
class MessageBuilder {

	/**
	 * @return list<array<string, mixed>>
	 */
	public function build_for_job( PushJob $job ): array {
		if ( $job->is_test ) {
			return array(
				array(
					'type' => 'text',
					'text' => __( 'bot-cat connection test — if you see this, your channel is wired up correctly.', 'bot-cat' ),
				),
			);
		}

		$title = (string) get_the_title( $job->post_id );
		$url   = (string) get_permalink( $job->post_id );

		if ( $title === '' && $url === '' ) {
			return array(
				array(
					'type' => 'text',
					'text' => __( 'A new post has been published.', 'bot-cat' ),
				),
			);
		}

		$body = trim( $title . "\n" . $url );

		/** @var list<array<string, mixed>> $messages */
		$messages = apply_filters(
			'botcat_push_messages',
			array(
				array(
					'type' => 'text',
					'text' => $body,
				),
			),
			$job
		);

		return $messages;
	}
}
