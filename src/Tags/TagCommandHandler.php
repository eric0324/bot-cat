<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

use BotCat\Subscribers\SubscriberRepository;

/**
 * Executes a parsed {@see TagCommand} against the data repositories and
 * returns the reply text to send back to the subscriber.
 *
 * Returning null means "do not reply" (text wasn't a command).
 */
class TagCommandHandler {

	public function __construct(
		private readonly TagRepository $tags,
		private readonly SubscriberTagRepository $joins,
		private readonly SubscriberRepository $subscribers
	) {
	}

	public function handle( string $line_user_id, TagCommand $command ): ?string {
		switch ( $command->type ) {
			case TagCommand::TYPE_NOT_A_COMMAND:
				return null;

			case TagCommand::TYPE_UNKNOWN:
				return $this->help_reply();

			case TagCommand::TYPE_LIST:
				return $this->list_reply( $line_user_id );

			case TagCommand::TYPE_SUBSCRIBE:
				return $this->subscribe_reply( $line_user_id, (string) $command->keyword );

			case TagCommand::TYPE_UNSUBSCRIBE:
				return $this->unsubscribe_reply( $line_user_id, (string) $command->keyword );
		}

		return null;
	}

	private function subscribe_reply( string $line_user_id, string $keyword ): string {
		$tag = $this->tags->find_by_keyword( $keyword );
		if ( $tag === null ) {
			return $this->unknown_keyword_reply();
		}

		$subscriber = $this->subscribers->find_by_line_id( $line_user_id );
		if ( $subscriber === null ) {
			return (string) __( 'Subscriber not found — please re-add the OA as a friend.', 'bot-cat' );
		}

		$this->joins->subscribe( $subscriber->id, $tag->id );

		return sprintf( '✅ 已訂閱「%s」推播', $tag->display_name );
	}

	private function unsubscribe_reply( string $line_user_id, string $keyword ): string {
		$tag = $this->tags->find_by_keyword( $keyword );
		if ( $tag === null ) {
			return $this->unknown_keyword_reply();
		}

		$subscriber = $this->subscribers->find_by_line_id( $line_user_id );
		if ( $subscriber === null ) {
			return (string) __( 'Subscriber not found — please re-add the OA as a friend.', 'bot-cat' );
		}

		$this->joins->unsubscribe( $subscriber->id, $tag->id );

		return sprintf( '已取消訂閱「%s」', $tag->display_name );
	}

	private function list_reply( string $line_user_id ): string {
		$subscriber = $this->subscribers->find_by_line_id( $line_user_id );
		if ( $subscriber === null ) {
			return (string) __( 'Subscriber not found — please re-add the OA as a friend.', 'bot-cat' );
		}

		$tag_ids = $this->joins->tag_ids_for_subscriber( $subscriber->id );
		if ( $tag_ids === array() ) {
			return (string) __( '目前沒有訂閱任何標籤。輸入「/tag 列表」查看可用關鍵字。', 'bot-cat' );
		}

		$all   = $this->tags->list_all();
		$names = array();
		foreach ( $all as $tag ) {
			if ( in_array( $tag->id, $tag_ids, true ) ) {
				$names[] = $tag->display_name;
			}
		}

		return sprintf(
			'目前訂閱：%s',
			implode( '、', $names )
		);
	}

	private function unknown_keyword_reply(): string {
		$tags = $this->tags->list_all();
		if ( $tags === array() ) {
			return (string) __( 'No tags defined yet.', 'bot-cat' );
		}

		$keywords = array_map( static fn( Tag $t ): string => $t->display_name, $tags );

		return sprintf(
			'找不到關鍵字。可用：%s',
			implode( '、', $keywords )
		);
	}

	private function help_reply(): string {
		return (string) __( '指令格式：/tag 訂閱 <關鍵字>、/tag 取消 <關鍵字>、/tag 列表', 'bot-cat' );
	}
}
