<?php

namespace Modules\Category\Tests\Unit;

use Tests\TestCase;
use Modules\Category\Models\Category;
use Modules\Category\Models\CategoryTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_fillable_attributes()
    {
        $category = new Category();

        $expectedFillable = [
            'slug',
            'icon',
            'is_active',
            'sort_order',
            'is_featured',
            'parent_id',
        ];

        $this->assertEquals($expectedFillable, $category->getFillable());
    }

    public function test_category_has_correct_casts()
    {
        $category = new Category();

        $expectedCasts = [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];

        $this->assertEquals($expectedCasts, $category->getCasts());
    }

    public function test_category_uses_translatable_trait()
    {
        $category = new Category();

        $this->assertContains('name', $category->translatedAttributes);
    }

    public function test_category_uses_sluggable_trait()
    {
        $category = new Category();

        $this->assertTrue(method_exists($category, 'sluggable'));
    }

    public function test_category_has_parent_relationship()
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
    }

    public function test_category_has_children_relationship()
    {
        $parent = Category::factory()->create();
        $child1 = Category::factory()->create(['parent_id' => $parent->id]);
        $child2 = Category::factory()->create(['parent_id' => $parent->id]);

        $children = $parent->children;

        $this->assertCount(2, $children);
        $this->assertEquals([$child1->id, $child2->id], $children->pluck('id')->sort()->values()->toArray());
    }

    public function test_category_has_sections_relationship()
    {
        $category = Category::factory()->create();

        // Assuming Section model exists and has many-to-many with categories
        // This test would need to be adjusted based on actual Section model
        $this->assertTrue(method_exists($category, 'sections'));
    }

    public function test_category_has_products_relationship()
    {
        $category = Category::factory()->create();

        // Assuming Product model exists
        $this->assertTrue(method_exists($category, 'products'));
    }

    public function test_category_has_coupons_relationship()
    {
        $category = Category::factory()->create();

        // Assuming Coupon model exists
        $this->assertTrue(method_exists($category, 'coupons'));
    }

    public function test_category_has_interests_relationship()
    {
        $category = Category::factory()->create();

        // Assuming Interest model exists
        $this->assertTrue(method_exists($category, 'interests'));
    }

    public function test_category_slug_generation()
    {
        $category = Category::factory()->create(['name' => 'Test Category']);

        $this->assertEquals('test-category', $category->slug);
    }

    public function test_medical_category_creation()
    {
        $category = Category::create([
            'slug' => 'medical',
            'icon' => 'uploads/icons/medical.png',
            'is_active' => true,
            'sort_order' => 4,
            'is_featured' => false,
        ]);

        $category->translations()->create([
            'name' => 'Medical',
            'locale' => 'en',
        ]);

        $category->translations()->create([
            'name' => 'طبي',
            'locale' => 'ar',
        ]);

        $this->assertDatabaseHas('categories', [
            'slug' => 'medical',
            'icon' => 'uploads/icons/medical.png',
            'is_active' => true,
            'sort_order' => 4,
            'is_featured' => false,
        ]);

        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'name' => 'Medical',
            'locale' => 'en',
        ]);

        $this->assertDatabaseHas('category_translations', [
            'category_id' => $category->id,
            'name' => 'طبي',
            'locale' => 'ar',
        ]);
    }
}
