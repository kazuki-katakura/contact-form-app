<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_belongs_to_category(): void
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $contact = Contact::factory()->for($category)->create();

        $this->assertInstanceOf(Category::class, $contact->category);
        $this->assertSame('商品トラブル', $contact->category->content);
    }

    public function test_contact_can_sync_multiple_tags(): void
    {
        $contact = Contact::factory()->create();
        [$first, $second, $third] = Tag::factory()->count(3)->create();

        $contact->tags()->sync([$first->id, $second->id]);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $contact->fresh()->tags->pluck('id')->all());

        $contact->tags()->sync([$second->id, $third->id]);
        $this->assertEqualsCanonicalizing([$second->id, $third->id], $contact->fresh()->tags->pluck('id')->all());
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $first->id]);
    }

    public function test_create_with_tags_saves_contact_and_pivot_records(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $contact = Contact::createWithTags($this->validContactData([
            'category_id' => $category->id,
            'tag_ids' => $tags->pluck('id')->all(),
        ]));

        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'email' => 'taro@example.com']);
        $this->assertSame(2, $contact->tags()->count());
    }

    public function test_update_with_tags_updates_attributes_and_syncs_tags(): void
    {
        $contact = Contact::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $contact->tags()->attach($oldTag);

        $contact->updateWithTags(['address' => '大阪府大阪市1-1', 'tag_ids' => [$newTag->id]]);

        $this->assertSame('大阪府大阪市1-1', $contact->fresh()->address);
        $this->assertSame([$newTag->id], $contact->fresh()->tags->pluck('id')->all());
    }

    public function test_full_name_and_gender_label_accessors(): void
    {
        $contact = Contact::factory()->make([
            'first_name' => '山田',
            'last_name' => '花子',
            'gender' => Contact::GENDER_FEMALE,
        ]);

        $this->assertSame('山田 花子', $contact->full_name);
        $this->assertSame('女性', $contact->gender_label);
    }

    public function test_deleting_contact_removes_pivot_records(): void
    {
        $contact = Contact::factory()->create();
        $contact->tags()->attach(Tag::factory()->count(2)->create());

        $contact->delete();

        $this->assertDatabaseCount('contact_tag', 0);
        $this->assertDatabaseCount('tags', 2);
    }
}
