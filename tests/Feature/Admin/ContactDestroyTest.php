<?php

namespace Tests\Feature\Admin;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_is_deleted_and_redirected_to_admin(): void
    {
        $contact = Contact::factory()->create();
        $contact->tags()->attach(Tag::factory()->create());

        $this->actingAs(User::factory()->create())
            ->delete("/admin/contacts/{$contact->id}")
            ->assertRedirect('/admin');

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
        $this->assertDatabaseCount('contact_tag', 0);
    }
}
