<?php
/**
 * @package BotCat\Tests\Unit\Rules
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Rules;

use BotCat\Rules\ExcludedCategories;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class ExcludedCategoriesTest extends TestCase
{
    public function testEmptyListMeansNothingIsExcluded(): void
    {
        Functions\expect('get_option')->andReturn([]);

        $this->assertFalse((new ExcludedCategories())->is_excluded(42));
    }

    public function testPostWithExcludedCategoryIsExcluded(): void
    {
        Functions\expect('get_option')->andReturn([5, 9]);
        Functions\expect('wp_get_post_categories')
            ->with(42, ['fields' => 'ids'])
            ->andReturn([3, 5, 7]);

        $this->assertTrue((new ExcludedCategories())->is_excluded(42));
    }

    public function testPostWithoutExcludedCategoryIsNotExcluded(): void
    {
        Functions\expect('get_option')->andReturn([5, 9]);
        Functions\expect('wp_get_post_categories')->andReturn([3, 7]);

        $this->assertFalse((new ExcludedCategories())->is_excluded(42));
    }

    public function testSaveStoresUniqueIntegerIds(): void
    {
        Functions\expect('update_option')->once()
            ->with(ExcludedCategories::OPTION, [3, 7, 9]);

        (new ExcludedCategories())->save(['3', 7, '9', '7']);

        $this->assertTrue(true);
    }
}
