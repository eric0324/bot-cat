<?php
/**
 * Base test case wiring Brain Monkey + Mockery teardown.
 *
 * @package BotCat\Tests
 */

declare(strict_types=1);

namespace BotCat\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\stubs([
            '__'             => static fn(string $text, ?string $domain = null): string => $text,
            '_e'             => static fn(string $text, ?string $domain = null): string => $text,
            '_x'             => static fn(string $text, ?string $ctx = null, ?string $domain = null): string => $text,
            '_n'             => static function (string $single, string $plural, int $number, ?string $domain = null): string {
                return $number === 1 ? $single : $plural;
            },
            'esc_html'       => static fn($s) => $s,
            'esc_attr'       => static fn($s) => $s,
            'esc_html__'     => static fn(string $text, ?string $domain = null): string => $text,
            'esc_attr__'     => static fn(string $text, ?string $domain = null): string => $text,
            'esc_html_e'     => static fn(string $text, ?string $domain = null): string => $text,
            'wp_kses_post'   => static fn($s) => $s,
            'sanitize_text_field' => static fn($s) => $s,
            'sanitize_key'   => static fn(string $key): string => strtolower(preg_replace('/[^a-z0-9_]/i', '', $key)),
            'wp_strip_all_tags' => static fn(string $s, bool $remove_breaks = false): string => trim(strip_tags($s)),
        ]);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
