<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_is_displayed_with_categories_and_tags(): void
    {
        $categories = Category::factory()->count(2)->sequence(
            ['content' => '商品のお届けについて'],
            ['content' => '商品トラブル'],
        )->create();
        $tags = Tag::factory()->count(2)->sequence(
            ['name' => '質問'],
            ['name' => '不具合報告'],
        )->create();

        $response = $this->get('/');

        $response->assertOk()
            ->assertViewIs('contact.index')
            ->assertViewHas('categories', fn ($viewCategories) => $viewCategories->pluck('id')->all() === $categories->pluck('id')->all())
            ->assertViewHas('tags', fn ($viewTags) => $viewTags->pluck('id')->all() === $tags->pluck('id')->all())
            ->assertSee('商品のお届けについて')
            ->assertSee('商品トラブル')
            ->assertSee('質問')
            ->assertSee('不具合報告');
    }

    public function test_thanks_page_is_displayed(): void
    {
        $this->get('/thanks')
            ->assertOk()
            ->assertViewIs('contact.thanks')
            ->assertSee('お問い合わせありがとうございました')
            ->assertSee('HOME');
    }
}
