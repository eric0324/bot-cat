<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * One page of subscriber results returned by SubscriberRepository::search().
 */
final class SubscriberPage {

	/**
	 * @param list<Subscriber> $rows
	 */
	public function __construct(
		public readonly array $rows,
		public readonly int $total
	) {
	}
}
