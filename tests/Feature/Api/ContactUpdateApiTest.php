<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_contact_and_syncs_tags(): void
    {
        $contact = Contact::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $contact->tags()->attach($oldTag);
        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $this->validContactData([
            'first_name' => '更新',
            'category_id' => $category->id,
            'tag_ids' => [$newTag->id],
        ]));

        $response->assertOk()
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.first_name', '更新')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.tags', [['id' => $newTag->id, 'name' => $newTag->name]]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'first_name' => '更新']);
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $oldTag->id]);
    }

    public function test_returns_404_for_unknown_id(): void
    {
        $category = Category::factory()->create();

        $this->putJson('/api/v1/contacts/999', $this->validContactData(['category_id' => $category->id]))
            ->assertNotFound()
            ->assertExactJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }

    public function test_validation_error_returns_422(): void
    {
        $contact = Contact::factory()->create();

        $this->putJson("/api/v1/contacts/{$contact->id}", ['detail' => str_repeat('あ', 121)])
            ->assertStatus(422)
            ->assertJsonPath('errors.detail.0', 'お問い合わせ内容は120文字以内で入力してください')
            ->assertJsonValidationErrors(['first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'category_id']);
    }
}
