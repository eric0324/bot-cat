<?php
/**
 * @package BotCat\Tests\Unit\Analytics
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Analytics;

use BotCat\Analytics\CsvExporter;
use BotCat\Tests\TestCase;

final class CsvExporterTest extends TestCase
{
    public function testOutputStartsWithUtf8Bom(): void
    {
        $out = (new CsvExporter())->to_string(['title'], [['Hello']]);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $out);
    }

    public function testCjkTitlesRoundTrip(): void
    {
        $out = (new CsvExporter())->to_string(['title'], [['測試文章']]);

        $this->assertStringContainsString('測試文章', $out);
        $this->assertStringNotContainsString('??', $out);
    }

    public function testEscapesQuotesAndCommas(): void
    {
        $out = (new CsvExporter())->to_string(['title'], [['Hello, "World"']]);

        $this->assertStringContainsString('"Hello, ""World"""', $out);
    }

    public function testHeaderAndRowsAreOnSeparateLines(): void
    {
        $out = (new CsvExporter())->to_string(['a', 'b'], [['1', '2'], ['3', '4']]);

        $lines = array_filter(explode("\n", str_replace("\xEF\xBB\xBF", '', $out)));

        $this->assertCount(3, $lines);
        $this->assertStringStartsWith('a,b', trim((string) array_shift($lines)));
    }
}
