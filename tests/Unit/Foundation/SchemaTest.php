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

    public function testPushLogsHasIndexesOnForeignKeys(): void
    {
        $schema = new Schema();
        $sql = $schema->sql_for('push_logs');

        $this->assertMatchesRegularExpression('/KEY\s+\w*job_id\w*\s*\(\s*job_id\s*\)/i', $sql);
        $this->assertMatchesRegularExpression('/KEY\s+\w*subscriber_id\w*\s*\(\s*subscriber_id\s*\)/i', $sql);
    }

    public function testInstallCallsDbDeltaWithEverySchemaStatement(): void
    {
        $captured = [];
        Functions\expect('dbDelta')
            ->times(3)
            ->andReturnUsing(function ($sql) use (&$captured) {
                $captured[] = $sql;
                return [];
            });

        $schema = new Schema();
        $schema->install();

        $this->assertCount(3, $captured);
        $joined = implode("\n", $captured);
        $this->assertStringContainsString('wp_botcat_subscribers', $joined);
        $this->assertStringContainsString('wp_botcat_push_jobs', $joined);
        $this->assertStringContainsString('wp_botcat_push_logs', $joined);
    }
}
