<?php
/**
 * @package BotCat\Tests\Unit\Shortener
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Shortener;

use BotCat\Shortener\ShortCodeGenerator;
use BotCat\Tests\TestCase;

final class ShortCodeGeneratorTest extends TestCase
{
    public function testGeneratesSixCharBase62Code(): void
    {
        $code = (new ShortCodeGenerator())->generate();

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[0-9A-Za-z]+$/', $code);
    }

    public function testGeneratesDistinctCodesAcrossInvocations(): void
    {
        $gen   = new ShortCodeGenerator();
        $codes = [];
        for ($i = 0; $i < 100; $i++) {
            $codes[$gen->generate()] = true;
        }

        $this->assertGreaterThan(95, count($codes), 'random generator should rarely repeat over 100 trials');
    }
}
