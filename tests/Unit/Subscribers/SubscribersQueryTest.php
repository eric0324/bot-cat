<?php
/**
 * @package BotCat\Tests\Unit\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Subscribers;

use BotCat\Subscribers\SubscribersQuery;
use BotCat\Tests\TestCase;

final class SubscribersQueryTest extends TestCase
{
    public function testDefaultsMatchListTableSpec(): void
    {
        $query = new SubscribersQuery();

        $this->assertSame('', $query->search);
        $this->assertNull($query->status);
        $this->assertSame('followed_at', $query->orderby);
        $this->assertSame('DESC', $query->order);
        $this->assertSame(1, $query->page);
        $this->assertSame(50, $query->per_page);
    }

    public function testOffsetIsZeroIndexedFromPage(): void
    {
        $this->assertSame(0, (new SubscribersQuery(page: 1, per_page: 50))->offset());
        $this->assertSame(50, (new SubscribersQuery(page: 2, per_page: 50))->offset());
        $this->assertSame(60, (new SubscribersQuery(page: 4, per_page: 20))->offset());
    }

    public function testOrderbyIsClampedToAllowList(): void
    {
        $bad = new SubscribersQuery(orderby: 'DROP TABLE');

        $this->assertSame('followed_at', $bad->orderby, 'unknown orderby must fall back to default');
    }

    public function testOrderIsClampedToAscOrDesc(): void
    {
        $bad = new SubscribersQuery(order: 'sideways');

        $this->assertSame('DESC', $bad->order);
    }

    public function testPerPageHasUpperBound(): void
    {
        $crazy = new SubscribersQuery(per_page: 99999);

        $this->assertLessThanOrEqual(500, $crazy->per_page);
    }

    public function testFromRequestParsesGetParameters(): void
    {
        $query = SubscribersQuery::from_request([
            's'        => 'Eric',
            'status'   => 'unfollowed',
            'orderby'  => 'status',
            'order'    => 'asc',
            'paged'    => '3',
        ]);

        $this->assertSame('Eric', $query->search);
        $this->assertSame('unfollowed', $query->status);
        $this->assertSame('status', $query->orderby);
        $this->assertSame('ASC', $query->order);
        $this->assertSame(3, $query->page);
    }
}
