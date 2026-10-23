<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactIndexApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_contacts_with_meta_and_default_per_page(): void
    {
        $contact = Contact::factory()->create(['created_at' => now()->addMinute()]);
        $contact->tags()->attach(Tag::factory()->create());
        Contact::factory()->count(24)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('data.0.id', $contact->id)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'category' => ['id', 'content'], 'first_name', 'last_name', 'gender', 'email',
                    'tel', 'address', 'building', 'detail', 'tags' => [['id', 'name']], 'created_at', 'updated_at',
                ]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_per_page_and_page_parameters(): void
    {
        Contact::factory()->count(12)->create();

        $this->getJson('/api/v1/contacts?per_page=5&page=3')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_search_filters(): void
    {
        $category = Category::factory()->create();
        $matched = Contact::factory()->for($category)->create([
            'first_name' => '田中', 'gender' => 2, 'created_at' => '2026-01-15 12:00:00',
        ]);
        Contact::factory()->for($category)->create(['first_name' => '田中', 'gender' => 1, 'created_at' => '2026-01-15 12:00:00']);
        Contact::factory()->create(['first_name' => '佐藤', 'gender' => 2, 'created_at' => '2026-01-15 12:00:00']);

        $this->getJson('/api/v1/contacts?keyword=田中')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/contacts?gender=2')->assertJsonCount(2, 'data');
        $this->getJson("/api/v1/contacts?category_id={$category->id}")->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/contacts?date=2026-01-15')->assertJsonCount(3, 'data');
        $this->getJson("/api/v1/contacts?keyword=田中&gender=2&category_id={$category->id}&date=2026-01-15")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matched->id);
    }

    public function test_validation_error_returns_422_with_japanese_messages(): void
    {
        $this->getJson('/api/v1/contacts?gender=0&category_id=999')
            ->assertStatus(422)
            ->assertJsonPath('errors.gender.0', '性別の値が不正です')
            ->assertJsonPath('errors.category_id.0', '選択されたカテゴリーが存在しません');

        $this->getJson('/api/v1/contacts?per_page=101')->assertStatus(422)->assertJsonValidationErrors('per_page');
    }

    public function test_returns_json_even_without_accept_header(): void
    {
        $this->get('/api/v1/contacts?gender=9')
            ->assertStatus(422)
            ->assertJsonValidationErrors('gender');
    }
}
