<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

use BotCat\Template\TemplateRenderer;
use BotCat\Template\TemplateRepository;

/**
 * Produces the LINE message payload for a given push job.
 *
 * Real publishes render the stored template (message-template
 * capability). Test pushes use a canned probe message. W4's
 * flex-message capability can override the whole payload via the
 * `botcat_push_messages` filter.
 */
class MessageBuilder {

	public function __construct(
		private readonly ?TemplateRepository $templates = null,
		private readonly ?TemplateRenderer $renderer = null
	) {
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function build_for_job( PushJob $job ): array {
		if ( $job->is_test ) {
			return $this->filtered(
				$job,
				array(
					array(
						'type' => 'text',
						'text' => __( 'bot-cat connection test — if you see this, your channel is wired up correctly.', 'bot-cat' ),
					),
				)
			);
		}

		$text = $this->render_for_post( $job->post_id );

		if ( $text === '' ) {
			$text = (string) __( 'A new post has been published.', 'bot-cat' );
		}

		return $this->filtered(
			$job,
			array(
				array(
					'type' => 'text',
					'text' => $text,
				),
			)
		);
	}

	private function render_for_post( int $post_id ): string {
		if ( $this->templates === null || $this->renderer === null ) {
			$title = (string) get_the_title( $post_id );
			$url   = (string) get_permalink( $post_id );
			return trim( $title . "\n" . $url );
		}

		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return '';
		}

		return $this->renderer->render( $this->templates->get(), $post );
	}

	/**
	 * @param list<array<string, mixed>> $messages
	 * @return list<array<string, mixed>>
	 */
	private function filtered( PushJob $job, array $messages ): array {
		/** @var list<array<string, mixed>> $filtered */
		$filtered = apply_filters( 'botcat_push_messages', $messages, $job );

		return is_array( $filtered ) ? $filtered : $messages;
	}
}
