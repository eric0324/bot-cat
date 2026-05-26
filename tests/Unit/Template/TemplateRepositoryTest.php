<?php
/**
 * @package BotCat\Tests\Unit\Template
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Template;

use BotCat\Template\TemplateRepository;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class TemplateRepositoryTest extends TestCase
{
    public function testDefaultTemplateMatchesSpec(): void
    {
        Functions\expect('get_option')
            ->with(TemplateRepository::OPTION, TemplateRepository::DEFAULT_TEMPLATE)
            ->andReturn(TemplateRepository::DEFAULT_TEMPLATE);

        $template = (new TemplateRepository())->get();

        $this->assertSame("{title}\n\n{excerpt}\n\n{permalink}", $template);
    }

    public function testSaveDelegatesToUpdateOption(): void
    {
        Functions\expect('update_option')->once()
            ->with(TemplateRepository::OPTION, '{title}\n{permalink}');

        (new TemplateRepository())->save('{title}\n{permalink}');

        $this->assertTrue(true, 'expectation on update_option is the real assertion');
    }

    public function testLengthCheckReturnsNullForUnderLimit(): void
    {
        $this->assertNull((new TemplateRepository())->length_check('Hello World'));
    }

    public function testLengthCheckReturnsErrorForOversize(): void
    {
        $long  = str_repeat('a', TemplateRepository::MAX_RENDERED_LENGTH + 1);
        $error = (new TemplateRepository())->length_check($long);

        $this->assertNotNull($error);
        $this->assertStringContainsString('5000', $error);
    }

    public function testStatusForLengthGivesGreenYellowRedBands(): void
    {
        $repo = new TemplateRepository();

        $this->assertSame(TemplateRepository::STATUS_GREEN, $repo->status_for_length(1234));
        $this->assertSame(TemplateRepository::STATUS_YELLOW, $repo->status_for_length(4500));
        $this->assertSame(TemplateRepository::STATUS_RED, $repo->status_for_length(5300));
    }
}
