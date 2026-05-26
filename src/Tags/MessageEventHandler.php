<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

use BotCat\Channel\ChannelSettingsRepository;
use BotCat\Channel\ReplyClient;

/**
 * Glue between the LINE webhook's `message` events and the tag
 * subscribe / unsubscribe / list flow.
 *
 * Non-command messages return without replying so unrelated chat is
 * not echoed back.
 */
class MessageEventHandler {

	public function __construct(
		private readonly TagCommandParser $parser,
		private readonly TagCommandHandler $commands,
		private readonly ChannelSettingsRepository $channel,
		private readonly ReplyClient $client
	) {
	}

	public function handle( string $line_user_id, string $reply_token, string $text ): void {
		$reply = $this->commands->handle( $line_user_id, $this->parser->parse( $text ) );

		if ( $reply === null ) {
			return;
		}

		$settings = $this->channel->get();
		if ( ! $settings->is_configured() ) {
			return;
		}

		$this->client->reply( $settings->access_token(), $reply_token, $reply );
	}
}
