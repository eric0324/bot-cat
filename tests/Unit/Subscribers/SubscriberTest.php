<?php
/**
 * @package BotCat\Tests\Unit\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Subscribers;

use BotCat\Subscribers\Subscriber;
use BotCat\Tests\TestCase;

final class SubscriberTest extends TestCase
{
    public function testIsActiveTrueWhenStatusActive(): void
    {
        $subscriber = new Subscriber(
            id: 1,
            line_user_id: 'U123',
            display_name: 'Eric',
            picture_url: null,
            status: Subscriber::STATUS_ACTIVE,
            followed_at: '2026-05-01 00:00:00',
            unfollowed_at: null
        );

        $this->assertTrue($subscriber->is_active());
        $this->assertFalse($subscriber->is_unfollowed());
    }

    public function testIsUnfollowedTrueWhenStatusUnfollowed(): void
    {
        $subscriber = new Subscriber(
            id: 2,
            line_user_id: 'U456',
            display_name: null,
            picture_url: null,
            status: Subscriber::STATUS_UNFOLLOWED,
            followed_at: '2026-05-01 00:00:00',
            unfollowed_at: '2026-05-10 00:00:00'
        );

        $this->assertFalse($subscriber->is_active());
        $this->assertTrue($subscriber->is_unfollowed());
    }

    public function testFromRowMapsDatabaseColumnsToTypedFields(): void
    {
        $row = [
            'id'             => '7',
            'line_user_id'   => 'U789',
            'display_name'   => 'Sarah',
            'picture_url'    => 'https://line/p.jpg',
            'status'         => 'active',
            'followed_at'    => '2026-05-15 12:00:00',
            'unfollowed_at'  => null,
        ];

        $subscriber = Subscriber::from_row($row);

        $this->assertSame(7, $subscriber->id);
        $this->assertSame('U789', $subscriber->line_user_id);
        $this->assertSame('Sarah', $subscriber->display_name);
    }
}
