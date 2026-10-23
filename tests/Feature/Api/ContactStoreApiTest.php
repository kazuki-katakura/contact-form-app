<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_contact_with_tags_and_returns_201(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $response = $this->postJson('/api/v1/contacts', $this->validContactData([
            'category_id' => $category->id,
            'tag_ids' => $tags->pluck('id')->all(),
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.first_name', '山田')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonCount(2, 'data.tags');
        $this->assertDatabaseHas('contacts', ['email' => 'taro@example.com', 'category_id' => $category->id]);
        $this->assertDatabaseCount('contact_tag', 2);
    }

    public function test_validation_error_returns_422_with_japanese_messages(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'gender' => 5,
            'tel' => '090-1234-5678',
            'category_id' => 999,
            'tag_ids' => [999],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.first_name.0', '姓を入力してください')
            ->assertJsonPath('errors.gender.0', '性別の値が不正です')
            ->assertJsonPath('errors.tel.0', '電話番号はハイフンなしの10〜11桁で入力してください')
            ->assertJsonPath('errors.category_id.0', '選択されたカテゴリーが存在しません');
        $this->assertSame('選択されたタグが存在しません', $response->json('errors')['tag_ids.0'][0]);
        $this->assertDatabaseCount('contacts', 0);
    }
}
