<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactDestroyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_contact_and_returns_204(): void
    {
        $contact = Contact::factory()->create();
        $contact->tags()->attach(Tag::factory()->create());

        $this->deleteJson("/api/v1/contacts/{$contact->id}")->assertNoContent();

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
        $this->assertDatabaseCount('contact_tag', 0);
    }

    public function test_returns_404_for_unknown_id(): void
    {
        $this->deleteJson('/api/v1/contacts/999')
            ->assertNotFound()
            ->assertExactJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }
}
