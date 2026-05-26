<?php
/**
 * @package BotCat\Tests\Unit\Foundation
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Foundation;

use BotCat\Foundation\Schema;
use BotCat\Foundation\SchemaVersion;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Requirement: Versioned database schema with migrations.
 */
final class SchemaVersionTest extends TestCase
{
    public function testFirstInstallStoresBundledVersionAndRunsInstall(): void
    {
        Functions\expect('get_option')
            ->with('botcat_db_version', '0.0.0')
            ->andReturn('0.0.0');
        Functions\expect('update_option')->once()
            ->with('botcat_db_version', '1.0.0');

        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())->method('install');

        $version = new SchemaVersion($schema, '1.0.0');
        $version->maybe_upgrade();
    }

    public function testNoUpgradeWhenStoredVersionMatchesBundledVersion(): void
    {
        Functions\expect('get_option')
            ->with('botcat_db_version', '0.0.0')
            ->andReturn('1.0.0');
        Functions\expect('update_option')->never();

        $schema = $this->createMock(Schema::class);
        $schema->expects($this->never())->method('install');

        $version = new SchemaVersion($schema, '1.0.0');
        $version->maybe_upgrade();
    }

    public function testUpgradeRunsWhenStoredVersionIsOlder(): void
    {
        Functions\expect('get_option')
            ->with('botcat_db_version', '0.0.0')
            ->andReturn('0.9.0');
        Functions\expect('update_option')->once()
            ->with('botcat_db_version', '1.2.0');

        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())->method('install');

        $version = new SchemaVersion($schema, '1.2.0');
        $version->maybe_upgrade();
    }
}
