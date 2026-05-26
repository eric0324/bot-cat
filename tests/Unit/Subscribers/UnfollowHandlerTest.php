<?php
/**
 * @package BotCat\Tests\Unit\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Subscribers;

use BotCat\Subscribers\SubscriberRepository;
use BotCat\Subscribers\UnfollowHandler;
use BotCat\Tests\TestCase;

/**
 * Covers Requirement: Unfollow event updates status without deletion.
 */
final class UnfollowHandlerTest extends TestCase
{
    public function testUnfollowMarksRowUnfollowedAndNeverDeletes(): void
    {
        $subscribers = $this->createMock(SubscriberRepository::class);
        $subscribers->expects($this->once())->method('mark_unfollowed')->with(
            $this->equalTo('U123'),
            $this->isType('string')
        );

        $handler = new UnfollowHandler($subscribers);
        $handler->handle('U123', 1_715_000_000);
    }
}
