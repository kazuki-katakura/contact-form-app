<?php

namespace Tests\Feature\Admin;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_tags_are_listed_on_admin_dashboard(): void
    {
        Tag::factory()->create(['name' => '新機能の要望']);

        $this->actingAs($this->user)->get('/admin')->assertOk()->assertSee('新機能の要望');
    }

    public function test_authenticated_user_can_create_tag(): void
    {
        $this->actingAs($this->user)
            ->post('/admin/tags', ['name' => '新機能の要望'])
            ->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', ['name' => '新機能の要望']);
    }

    public function test_create_tag_validation_errors(): void
    {
        Tag::factory()->create(['name' => '質問']);
        $this->actingAs($this->user);

        $this->from('/admin')->post('/admin/tags', ['name' => ''])
            ->assertRedirect('/admin')
            ->assertSessionHasErrors(['name' => 'タグ名を入力してください']);
        $this->from('/admin')->post('/admin/tags', ['name' => str_repeat('あ', 51)])
            ->assertSessionHasErrors(['name' => 'タグ名は50文字以内で入力してください']);
        $this->from('/admin')->post('/admin/tags', ['name' => '質問'])
            ->assertSessionHasErrors(['name' => 'そのタグ名は既に使用されています']);

        $this->assertDatabaseCount('tags', 1);
    }

    public function test_edit_page_shows_current_tag_name(): void
    {
        $tag = Tag::factory()->create(['name' => '不具合報告']);

        $this->actingAs($this->user)
            ->get("/admin/tags/{$tag->id}/edit")
            ->assertOk()
            ->assertViewIs('admin.tags.edit')
            ->assertSee('value="不具合報告"', false);
    }

    public function test_authenticated_user_can_update_tag(): void
    {
        $tag = Tag::factory()->create(['name' => '不具合報告']);

        $this->actingAs($this->user)
            ->put("/admin/tags/{$tag->id}", ['name' => 'バグ報告'])
            ->assertRedirect('/admin');

        $this->assertSame('バグ報告', $tag->fresh()->name);
    }

    public function test_tag_can_keep_its_own_name_on_update(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $this->actingAs($this->user)
            ->put("/admin/tags/{$tag->id}", ['name' => '質問'])
            ->assertRedirect('/admin')
            ->assertSessionHasNoErrors();
    }

    public function test_update_tag_validation_errors_return_to_edit_page(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);
        Tag::factory()->create(['name' => '要望']);
        $editUrl = "/admin/tags/{$tag->id}/edit";
        $this->actingAs($this->user);

        $this->from($editUrl)->put("/admin/tags/{$tag->id}", ['name' => '要望'])
            ->assertRedirect($editUrl)
            ->assertSessionHasErrors(['name' => 'そのタグ名は既に使用されています']);
        $this->from($editUrl)->put("/admin/tags/{$tag->id}", ['name' => ''])
            ->assertSessionHasErrors(['name' => 'タグ名を入力してください']);
        $this->from($editUrl)->put("/admin/tags/{$tag->id}", ['name' => str_repeat('a', 51)])
            ->assertSessionHasErrors(['name' => 'タグ名は50文字以内で入力してください']);

        $this->assertSame('質問', $tag->fresh()->name);
    }

    public function test_authenticated_user_can_delete_tag_and_pivot_records(): void
    {
        $tag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        $contact->tags()->attach($tag);

        $this->actingAs($this->user)
            ->delete("/admin/tags/{$tag->id}")
            ->assertRedirect('/admin');

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('contact_tag', ['tag_id' => $tag->id]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_guest_cannot_operate_tags(): void
    {
        $tag = Tag::factory()->create(['name' => '質問']);

        $this->get("/admin/tags/{$tag->id}/edit")->assertRedirect('/login');
        $this->post('/admin/tags', ['name' => '新しいタグ'])->assertRedirect('/login');
        $this->put("/admin/tags/{$tag->id}", ['name' => '変更後'])->assertRedirect('/login');
        $this->delete("/admin/tags/{$tag->id}")->assertRedirect('/login');

        $this->assertDatabaseCount('tags', 1);
        $this->assertSame('質問', $tag->fresh()->name);
    }
}
