<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

/**
 * Composes the onboarding message a brand-new subscriber receives.
 *
 * Returns null when no tags exist — the welcome would otherwise be a
 * confusing "subscribe to nothing" message, so we skip it entirely
 * and let the regular pushes speak for themselves.
 */
class WelcomeMessageBuilder {

	public function __construct( private readonly TagRepository $tags ) {
	}

	public function build(): ?string {
		$tags = $this->tags->list_all();
		if ( $tags === array() ) {
			return null;
		}

		$keywords = array_map( static fn( Tag $tag ): string => $tag->display_name, $tags );

		return sprintf(
			"%s\n\n%s\n\n%s\n  /tag 訂閱 <%s>\n  /tag 取消 <%s>\n  /tag 列表",
			(string) __( '歡迎！您可以訂閱感興趣的主題推播。', 'bot-cat' ),
			(string) __( '可用主題：', 'bot-cat' ) . implode( '、', $keywords ),
			(string) __( '指令格式：', 'bot-cat' ),
			(string) __( '關鍵字', 'bot-cat' ),
			(string) __( '關鍵字', 'bot-cat' )
		);
	}
}
