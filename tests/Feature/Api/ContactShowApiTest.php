<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactShowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_contact_detail_with_category_and_tags(): void
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $contact = Contact::factory()->for($category)->create(['first_name' => '山田']);
        $tag = Tag::factory()->create(['name' => '質問']);
        $contact->tags()->attach($tag);

        $this->getJson("/api/v1/contacts/{$contact->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.first_name', '山田')
            ->assertJsonPath('data.category', ['id' => $category->id, 'content' => '商品トラブル'])
            ->assertJsonPath('data.tags', [['id' => $tag->id, 'name' => '質問']]);
    }

    public function test_returns_404_json_for_unknown_id(): void
    {
        $this->getJson('/api/v1/contacts/999')
            ->assertNotFound()
            ->assertExactJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }
}
