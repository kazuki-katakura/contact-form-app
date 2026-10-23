<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_and_tags_are_saved_and_redirected_to_thanks(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $response = $this->post('/contacts', $this->validContactData([
            'category_id' => $category->id,
            'tag_ids' => $tags->pluck('id')->all(),
        ]));

        $response->assertRedirect('/thanks');
        $this->assertDatabaseHas('contacts', [
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => '千駄ヶ谷マンション101',
            'detail' => '商品の配送日について教えてください。',
        ]);
        $contact = Contact::first();
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $tag->id]);
        }
    }

    public function test_contact_without_tags_and_building_is_saved(): void
    {
        $category = Category::factory()->create();

        $this->post('/contacts', $this->validContactData([
            'category_id' => $category->id,
            'building' => '',
            'tag_ids' => [],
        ]))->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', ['email' => 'taro@example.com', 'building' => null]);
        $this->assertDatabaseCount('contact_tag', 0);
    }

    public function test_validation_error_redirects_with_errors_and_saves_nothing(): void
    {
        $category = Category::factory()->create();

        $response = $this->post('/contacts', $this->validContactData([
            'category_id' => $category->id,
            'tel' => '090-1234-5678',
            'first_name' => '',
        ]));

        $response->assertRedirect('/')
            ->assertSessionHasErrors([
                'first_name' => '姓を入力してください',
                'tel' => '電話番号はハイフンなしの10〜11桁で入力してください',
            ]);
        $this->assertDatabaseCount('contacts', 0);
    }
}
