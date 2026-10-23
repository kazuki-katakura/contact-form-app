<?php

namespace Tests\Feature\Admin;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertOk()
            ->assertViewIs('admin.index')
            ->assertViewHasAll(['contacts', 'categories', 'tags']);
    }

    public function test_guest_is_redirected_to_login_from_admin_pages(): void
    {
        $contact = Contact::factory()->create();

        $this->get('/admin')->assertRedirect('/login');
        $this->get("/admin/contacts/{$contact->id}")->assertRedirect('/login');
        $this->delete("/admin/contacts/{$contact->id}")->assertRedirect('/login');
        $this->get('/contacts/export')->assertRedirect('/login');

        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }
}
