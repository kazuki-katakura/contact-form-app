<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_belongs_to_many_contacts_through_pivot_table(): void
    {
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(3)->create();
        $tag->contacts()->attach($contacts->pluck('id'));

        $this->assertCount(3, $tag->contacts);
        $this->assertEqualsCanonicalizing($contacts->pluck('id')->all(), $tag->contacts->pluck('id')->all());
        foreach ($contacts as $contact) {
            $this->assertDatabaseHas('contact_tag', ['contact_id' => $contact->id, 'tag_id' => $tag->id]);
        }
    }

    public function test_deleting_tag_removes_pivot_records_but_keeps_contacts(): void
    {
        $tag = Tag::factory()->create();
        $tag->contacts()->attach(Contact::factory()->count(2)->create()->pluck('id'));

        $tag->delete();

        $this->assertDatabaseCount('contact_tag', 0);
        $this->assertDatabaseCount('contacts', 2);
    }
}
