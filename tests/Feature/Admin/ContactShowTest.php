<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_detail_is_displayed_with_category_and_tags(): void
    {
        $category = Category::factory()->create(['content' => 'ショップへのお問い合わせ']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => '佐藤',
            'last_name' => '健',
            'gender' => 3,
            'email' => 'ken@example.com',
            'tel' => '0312345678',
            'address' => '東京都新宿区1-1-1',
            'building' => '新宿ビル202',
            'detail' => '営業時間を教えてください。',
        ]);
        $contact->tags()->attach(Tag::factory()->count(2)->sequence(['name' => '質問'], ['name' => 'ご意見'])->create());

        $response = $this->actingAs(User::factory()->create())->get("/admin/contacts/{$contact->id}");

        $response->assertOk()
            ->assertViewIs('admin.show')
            ->assertViewHas('contact', fn (Contact $viewContact) => $viewContact->is($contact)
                && $viewContact->relationLoaded('category')
                && $viewContact->relationLoaded('tags'))
            ->assertSee('佐藤 健')
            ->assertSee('その他')
            ->assertSee('ken@example.com')
            ->assertSee('0312345678')
            ->assertSee('東京都新宿区1-1-1')
            ->assertSee('新宿ビル202')
            ->assertSee('ショップへのお問い合わせ')
            ->assertSee('質問, ご意見')
            ->assertSee('営業時間を教えてください。');
    }

    public function test_not_found_for_unknown_contact(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/contacts/999')->assertNotFound();
    }
}
