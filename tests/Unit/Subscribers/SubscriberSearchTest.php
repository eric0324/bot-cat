<?php
/**
 * @package BotCat\Tests\Unit\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Subscribers;

use BotCat\Subscribers\Subscriber;
use BotCat\Subscribers\SubscriberRepository;
use BotCat\Subscribers\SubscribersQuery;
use BotCat\Tests\TestCase;

/**
 * Covers SubscriberRepository::search() — the data path behind the
 * SubscriberListTable.
 */
final class SubscriberSearchTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();

        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            /** @var list<array{0:string,1:string,2:list<mixed>}> */
            public array $queries = [];
            /** @var list<array<string, mixed>> */
            public array $rows_to_return = [];
            public string $count_to_return = '0';
            public function prepare(string $query, ...$args): string
            {
                $this->queries[] = ['prepare', $query, $args];
                return $query;
            }
            public function get_var(string $sql): ?string
            {
                $this->queries[] = ['get_var', $sql, []];
                return $this->count_to_return;
            }
            public function get_results(string $sql, $output = OBJECT): array
            {
                $this->queries[] = ['get_results', $sql, []];
                return $this->rows_to_return;
            }
            public function query(string $sql): int
            {
                return 0;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testSearchReturnsRowsAndTotal(): void
    {
        $this->wpdb->count_to_return = '125';
        $this->wpdb->rows_to_return  = [
            [
                'id' => '1', 'line_user_id' => 'U1', 'display_name' => 'Eric',
                'picture_url' => null, 'status' => 'active',
                'followed_at' => '2026-05-01 00:00:00', 'unfollowed_at' => null,
            ],
        ];

        $page = (new SubscriberRepository())->search(new SubscribersQuery());

        $this->assertSame(125, $page->total);
        $this->assertCount(1, $page->rows);
        $this->assertInstanceOf(Subscriber::class, $page->rows[0]);
        $this->assertSame('Eric', $page->rows[0]->display_name);
    }

    public function testSearchIncludesLikePatternWhenSearchTermProvided(): void
    {
        (new SubscriberRepository())->search(new SubscribersQuery(search: 'Eric'));

        $found_like = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'LIKE')) {
                $this->assertContains('%Eric%', $q[2]);
                $found_like = true;
            }
        }
        $this->assertTrue($found_like, 'search term must be passed to a LIKE clause');
    }

    public function testStatusFilterNarrowsResults(): void
    {
        (new SubscriberRepository())->search(new SubscribersQuery(status: 'unfollowed'));

        $found_status = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'status = %s')) {
                $this->assertContains('unfollowed', $q[2]);
                $found_status = true;
            }
        }
        $this->assertTrue($found_status, 'status filter must reach the prepared statement');
    }

    public function testOrderbyAndPaginationAreApplied(): void
    {
        (new SubscriberRepository())->search(
            new SubscribersQuery(orderby: 'status', order: 'ASC', page: 3, per_page: 20)
        );

        $found_limit = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'ORDER BY status ASC') && str_contains($q[1], 'LIMIT')) {
                $this->assertContains(20, $q[2]);
                $this->assertContains(40, $q[2], 'offset for page 3 with 20 per_page should be 40');
                $found_limit = true;
            }
        }
        $this->assertTrue($found_limit, 'orderby + pagination must appear in the SELECT statement');
    }
}
