<?php
/**
 * @package BotCat\Tests\Unit\Shortener
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Shortener;

use BotCat\Shortener\ShortCodeGenerator;
use BotCat\Shortener\ShortLink;
use BotCat\Shortener\ShortLinkRepository;
use BotCat\Tests\TestCase;

final class ShortLinkRepositoryTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            public int $insert_id = 0;
            /** @var list<array{0:string,1:string,2:array}> */
            public array $queries = [];
            /** @var array<string,mixed>|null */
            public ?array $next_row = null;
            public function prepare(string $query, ...$args): string
            {
                $this->queries[] = ['prepare', $query, $args];
                return $query;
            }
            public function get_row(string $sql, $output = OBJECT): ?array
            {
                $this->queries[] = ['get_row', $sql, []];
                return $this->next_row;
            }
            public function get_results(string $sql, $output = OBJECT): array
            {
                $this->queries[] = ['get_results', $sql, []];
                return [];
            }
            public function get_var(string $sql): ?string
            {
                $this->queries[] = ['get_var', $sql, []];
                return null;
            }
            public function insert(string $table, array $data, array $formats): int
            {
                $this->queries[] = ['insert', $table, $data];
                $this->insert_id = 42;
                return 1;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testCreateForPostInsertsNewRowWhenAbsent(): void
    {
        $generator = $this->createMock(ShortCodeGenerator::class);
        $generator->method('generate')->willReturn('aB3xY9');

        $repo = new ShortLinkRepository($generator);
        $link = $repo->create_for_post(42, 'https://x.test/p/42');

        $this->assertSame('aB3xY9', $link->code);
        $this->assertSame(42, $link->id);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'insert' && $q[1] === 'wp_botcat_short_links') {
                $found = true;
                $this->assertSame('aB3xY9', $q[2]['code']);
                $this->assertSame(42, $q[2]['post_id']);
                $this->assertSame('https://x.test/p/42', $q[2]['original_url']);
            }
        }
        $this->assertTrue($found);
    }

    public function testCreateForPostReusesExistingRowWhenUrlUnchanged(): void
    {
        $this->wpdb->next_row = [
            'id' => '7', 'code' => 'aB3xY9', 'post_id' => '42',
            'original_url' => 'https://x.test/p/42', 'created_at' => '2026-05-26',
        ];

        $generator = $this->createMock(ShortCodeGenerator::class);
        $generator->expects($this->never())->method('generate');

        $repo = new ShortLinkRepository($generator);
        $link = $repo->create_for_post(42, 'https://x.test/p/42');

        $this->assertSame(7, $link->id);
        $this->assertSame('aB3xY9', $link->code);
    }

    public function testFindByCodeReturnsLinkOrNull(): void
    {
        $this->wpdb->next_row = [
            'id' => '7', 'code' => 'aB3xY9', 'post_id' => '42',
            'original_url' => 'https://x.test/p/42', 'created_at' => '',
        ];

        $repo = new ShortLinkRepository($this->createMock(ShortCodeGenerator::class));
        $link = $repo->find_by_code('aB3xY9');

        $this->assertInstanceOf(ShortLink::class, $link);
        $this->assertSame('https://x.test/p/42', $link->original_url);

        $this->wpdb->next_row = null;
        $this->assertNull($repo->find_by_code('zzzzzz'));
    }
}
