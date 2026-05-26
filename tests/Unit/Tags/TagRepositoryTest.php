<?php
/**
 * @package BotCat\Tests\Unit\Tags
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Tags;

use BotCat\Tags\Tag;
use BotCat\Tags\TagRepository;
use BotCat\Tests\TestCase;

final class TagRepositoryTest extends TestCase
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
            /** @var list<array<string,mixed>>|null */
            public ?array $next_rows = null;
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
                return $this->next_rows ?? [];
            }
            public function get_var(string $sql): ?string
            {
                $this->queries[] = ['get_var', $sql, []];
                return null;
            }
            public function query(string $sql): int
            {
                $this->queries[] = ['query', $sql, []];
                return 1;
            }
            public function insert(string $table, array $data, array $formats): int
            {
                $this->queries[] = ['insert', $table, $data];
                $this->insert_id = 7;
                return 1;
            }
            public function update(string $table, array $data, array $where, array $df = array(), array $wf = array()): int
            {
                $this->queries[] = ['update', $table, array_merge($data, $where)];
                return 1;
            }
            public function delete(string $table, array $where, array $wf = array()): int
            {
                $this->queries[] = ['delete', $table, $where];
                return 1;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testCreateInsertsAndReturnsId(): void
    {
        $id = (new TagRepository())->create('tech', '科技', '科技', [3, 4]);

        $this->assertSame(7, $id);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'insert' && $q[1] === 'wp_botcat_tags') {
                $found = true;
                $this->assertSame('tech', $q[2]['slug']);
                $this->assertSame('科技', $q[2]['display_name']);
                $this->assertSame('科技', $q[2]['keyword']);
                $this->assertSame('[3,4]', $q[2]['mapped_categories']);
            }
        }
        $this->assertTrue($found);
    }

    public function testFindBySlugReturnsTagWhenPresent(): void
    {
        $this->wpdb->next_row = [
            'id' => '1', 'slug' => 'tech', 'display_name' => '科技',
            'keyword' => '科技', 'mapped_categories' => '[3,4]',
            'created_at' => '2026-05-26', 'updated_at' => '2026-05-26',
        ];

        $tag = (new TagRepository())->find_by_slug('tech');

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertSame('tech', $tag->slug);
        $this->assertSame([3, 4], $tag->mapped_categories);
    }

    public function testFindByKeywordIsCaseInsensitive(): void
    {
        $this->wpdb->next_row = [
            'id' => '1', 'slug' => 'tech', 'display_name' => '科技',
            'keyword' => '科技', 'mapped_categories' => '[]',
            'created_at' => '', 'updated_at' => '',
        ];

        (new TagRepository())->find_by_keyword('科技');

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'keyword')) {
                $found = true;
                $this->assertContains('科技', $q[2]);
            }
        }
        $this->assertTrue($found);
    }

    public function testFindForCategoriesReturnsTagsWithOverlap(): void
    {
        $this->wpdb->next_rows = [
            ['id' => '1', 'slug' => 'tech', 'display_name' => '科技', 'keyword' => '科技', 'mapped_categories' => '[3,4]', 'created_at' => '', 'updated_at' => ''],
            ['id' => '2', 'slug' => 'finance', 'display_name' => '財經', 'keyword' => '財經', 'mapped_categories' => '[10]', 'created_at' => '', 'updated_at' => ''],
        ];

        $tags = (new TagRepository())->find_for_categories([3, 99]);

        $this->assertCount(1, $tags);
        $this->assertSame('tech', $tags[0]->slug);
    }

    public function testDeleteAlsoCleansSubscriberTagJoinRows(): void
    {
        (new TagRepository())->delete(7);

        $deleted_tag = false;
        $deleted_joins = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'delete' && $q[1] === 'wp_botcat_tags' && ($q[2]['id'] ?? null) === 7) {
                $deleted_tag = true;
            }
            if ($q[0] === 'prepare' && str_contains($q[1], 'DELETE FROM wp_botcat_subscriber_tags')) {
                $deleted_joins = true;
                $this->assertContains(7, $q[2]);
            }
        }
        $this->assertTrue($deleted_tag);
        $this->assertTrue($deleted_joins);
    }
}
