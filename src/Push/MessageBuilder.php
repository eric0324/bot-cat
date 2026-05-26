<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

use BotCat\Flex\FlexMessageBuilder;
use BotCat\Flex\FlexTemplateRepository;
use BotCat\Foundation\Edition;
use BotCat\Shortener\LinkRewriter;
use BotCat\Template\TemplateRenderer;
use BotCat\Template\TemplateRepository;
use Throwable;

/**
 * Produces the LINE message payload for a given push job.
 *
 * Edition behavior:
 *  - Test jobs always render a canned probe message.
 *  - Pro builds render the Flex template; any exception inside the
 *    Flex builder fires `botcat_flex_render_failed` and we fall through
 *    to the free-edition text template so delivery still happens.
 *  - Free builds render the text template.
 *
 * Either result can be overridden by the `botcat_push_messages` filter.
 */
class MessageBuilder {

	public function __construct(
		private readonly ?TemplateRepository $templates = null,
		private readonly ?TemplateRenderer $renderer = null,
		private readonly ?FlexTemplateRepository $flex_templates = null,
		private readonly ?FlexMessageBuilder $flex_builder = null,
		private readonly ?Edition $edition = null,
		private readonly ?LinkRewriter $link_rewriter = null
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

		if ( $this->should_attempt_flex() ) {
			$flex = $this->build_flex( $job );
			if ( $flex !== null ) {
				return $this->filtered( $job, $this->rewrite_messages( array( $flex ), $job ) );
			}
		}

		return $this->filtered( $job, $this->rewrite_messages( array( $this->build_text( $job ) ), $job ) );
	}

	/**
	 * @param list<array<string, mixed>> $messages
	 * @return list<array<string, mixed>>
	 */
	private function rewrite_messages( array $messages, PushJob $job ): array {
		if ( $this->link_rewriter === null || $job->post_id <= 0 ) {
			return $messages;
		}

		$permalink = (string) get_permalink( $job->post_id );
		if ( $permalink === '' ) {
			return $messages;
		}

		return array_map(
			function ( array $msg ) use ( $job, $permalink ): array {
				$json = wp_json_encode( $msg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( ! is_string( $json ) ) {
					return $msg;
				}
				$rewritten = $this->link_rewriter->rewrite( $json, $job->post_id, $permalink );
				$decoded   = json_decode( $rewritten, true );
				return is_array( $decoded ) ? $decoded : $msg;
			},
			$messages
		);
	}

	private function should_attempt_flex(): bool {
		if ( $this->flex_builder === null || $this->flex_templates === null ) {
			return false;
		}
		if ( $this->edition === null ) {
			return false;
		}
		return $this->edition->is_pro();
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function build_flex( PushJob $job ): ?array {
		try {
			$template = $this->flex_templates->get();
			$post     = $job->post_id > 0 ? get_post( $job->post_id ) : null;
			if ( ! is_object( $post ) ) {
				return null;
			}
			return $this->flex_builder->build( $template, $post );
		} catch ( Throwable $e ) {
			do_action( 'botcat_flex_render_failed', $job, $e );
			return null;
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function build_text( PushJob $job ): array {
		$text = $this->render_text_for_post( $job->post_id );

		if ( $text === '' ) {
			$text = (string) __( 'A new post has been published.', 'bot-cat' );
		}

		return array(
			'type' => 'text',
			'text' => $text,
		);
	}

	private function render_text_for_post( int $post_id ): string {
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
