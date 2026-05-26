<?php
/**
 * @package BotCat\Tests\Unit\Foundation
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Foundation;

use BotCat\Foundation\Schema;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Requirement: Core database tables (table existence + indexes).
 */
final class SchemaTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();

        global $wpdb;
        $wpdb = new \stdClass();
        $wpdb->prefix = 'wp_';
        $wpdb->charset = 'utf8mb4';
        $wpdb->collate = 'utf8mb4_unicode_ci';
        $this->wpdb = $wpdb;

        Functions\stubs([
            'sanitize_key' => static fn($s) => $s,
        ]);
    }

    public function testTableNamesReturnedAreAllBotcatPrefixed(): void
    {
        $schema = new Schema();

        foreach ($schema->tables() as $name) {
            $this->assertStringStartsWith('wp_botcat_', $name, "table $name not botcat-prefixed");
        }
    }

    public function testTablesIncludeSubscribersPushJobsPushLogs(): void
    {
        $schema = new Schema();
        $tables = $schema->tables();

        $this->assertContains('wp_botcat_subscribers', $tables);
        $this->assertContains('wp_botcat_push_jobs', $tables);
        $this->assertContains('wp_botcat_push_logs', $tables);
    }

    public function testTagsAndSubscriberTagsTablesAddedInV1Point2(): void
    {
        $tables = (new Schema())->tables();

        $this->assertContains('wp_botcat_tags', $tables);
        $this->assertContains('wp_botcat_subscriber_tags', $tables);
    }

    public function testTagsTableHasUniqueSlug(): void
    {
        $sql = (new Schema())->sql_for('tags');

        $this->assertMatchesRegularExpression('/UNIQUE KEY\s+slug\s*\(\s*slug\s*\)/i', $sql);
    }

    public function testSubscriberTagsHasCompositePrimaryKey(): void
    {
        $sql = (new Schema())->sql_for('subscriber_tags');

        $this->assertMatchesRegularExpression('/PRIMARY KEY\s+\(\s*subscriber_id\s*,\s*tag_id\s*\)/i', $sql);
    }

    public function testPushLogsHasIndexesOnForeignKeysAndJobStatus(): void
    {
        $schema = new Schema();
        $sql = $schema->sql_for('push_logs');

        $this->assertMatchesRegularExpression('/KEY\s+\w+\s*\(\s*job_id\s*,\s*status\s*\)/i', $sql, 'must have composite (job_id, status) index per W2 spec');
        $this->assertMatchesRegularExpression('/KEY\s+\w*subscriber_id\w*\s*\(\s*subscriber_id\s*\)/i', $sql);
    }

    public function testPushJobsHasPostStatusCompositeIndex(): void
    {
        $sql = (new Schema())->sql_for('push_jobs');

        $this->assertMatchesRegularExpression('/KEY\s+\w+\s*\(\s*post_id\s*,\s*status\s*\)/i', $sql, '(post_id, status) composite index required for idempotency lookup');
    }

    public function testPushJobsHasTriggeredAtAndTestFlag(): void
    {
        $sql = (new Schema())->sql_for('push_jobs');

        $this->assertStringContainsString('triggered_at', $sql);
        $this->assertStringContainsString('is_test', $sql);
        $this->assertStringContainsString('post_type', $sql);
    }

    public function testPushLogsHasIsTestFlag(): void
    {
        $sql = (new Schema())->sql_for('push_logs');

        $this->assertStringContainsString('is_test', $sql);
        $this->assertStringContainsString('attempts', $sql);
    }

    public function testInstallCallsDbDeltaWithEverySchemaStatement(): void
    {
        $captured = [];
        Functions\expect('dbDelta')
            ->times(count(Schema::TABLES))
            ->andReturnUsing(function ($sql) use (&$captured) {
                $captured[] = $sql;
                return [];
            });

        $schema = new Schema();
        $schema->install();

        $this->assertCount(count(Schema::TABLES), $captured);
        $joined = implode("\n", $captured);
        $this->assertStringContainsString('wp_botcat_subscribers', $joined);
        $this->assertStringContainsString('wp_botcat_push_jobs', $joined);
        $this->assertStringContainsString('wp_botcat_push_logs', $joined);
        $this->assertStringContainsString('wp_botcat_tags', $joined);
        $this->assertStringContainsString('wp_botcat_subscriber_tags', $joined);
        $this->assertStringContainsString('wp_botcat_short_links', $joined);
        $this->assertStringContainsString('wp_botcat_short_link_clicks', $joined);
    }
}
