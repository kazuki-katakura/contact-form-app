<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    private function idsFor(array $query): array
    {
        return $this->get('/admin?'.http_build_query($query))
            ->assertOk()
            ->viewData('contacts')
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
    }

    public function test_contacts_are_paginated_by_seven_in_newest_order(): void
    {
        $contacts = Contact::factory()->count(10)->sequence(
            fn ($sequence) => ['created_at' => now()->subMinutes(10 - $sequence->index)]
        )->create();

        $firstPage = $this->get('/admin')->assertOk()->viewData('contacts');
        $this->assertSame(7, $firstPage->count());
        $this->assertSame(10, $firstPage->total());
        $this->assertSame($contacts->last()->id, $firstPage->first()->id);

        $secondPage = $this->get('/admin?page=2')->assertOk()->viewData('contacts');
        $this->assertSame(3, $secondPage->count());
        $this->assertSame($contacts->first()->id, $secondPage->last()->id);
    }

    public function test_keyword_matches_partial_name_full_name_and_email(): void
    {
        $yamada = Contact::factory()->create(['first_name' => '山田', 'last_name' => '太郎', 'email' => 'taro@example.com']);
        $suzuki = Contact::factory()->create(['first_name' => '鈴木', 'last_name' => '花子', 'email' => 'hanako@sample.jp']);

        $this->assertSame([$yamada->id], $this->idsFor(['keyword' => '山']));
        $this->assertSame([$suzuki->id], $this->idsFor(['keyword' => '花子']));
        $this->assertSame([$yamada->id], $this->idsFor(['keyword' => '山田 太郎']));
        $this->assertSame([$suzuki->id], $this->idsFor(['keyword' => 'sample.jp']));
        $this->assertSame([$yamada->id], $this->idsFor(['keyword' => 'taro@example.com']));
    }

    public function test_gender_filter_and_zero_means_all(): void
    {
        $male = Contact::factory()->create(['gender' => 1]);
        $female = Contact::factory()->create(['gender' => 2]);
        $other = Contact::factory()->create(['gender' => 3]);

        $this->assertSame([$female->id], $this->idsFor(['gender' => 2]));
        $this->assertSame([$male->id, $female->id, $other->id], $this->idsFor(['gender' => 0]));
    }

    public function test_category_filter(): void
    {
        $category = Category::factory()->create();
        $matched = Contact::factory()->for($category)->create();
        Contact::factory()->create();

        $this->assertSame([$matched->id], $this->idsFor(['category_id' => $category->id]));
    }

    public function test_date_filter(): void
    {
        $matched = Contact::factory()->create(['created_at' => '2026-01-15 10:00:00']);
        Contact::factory()->create(['created_at' => '2026-01-16 10:00:00']);

        $this->assertSame([$matched->id], $this->idsFor(['date' => '2026-01-15']));
    }

    public function test_all_filters_can_be_combined(): void
    {
        $category = Category::factory()->create();
        $matched = Contact::factory()->for($category)->create([
            'first_name' => '山田', 'gender' => 1, 'created_at' => '2026-01-15 09:00:00',
        ]);
        Contact::factory()->for($category)->create([
            'first_name' => '山田', 'gender' => 2, 'created_at' => '2026-01-15 09:00:00',
        ]);
        Contact::factory()->create(['first_name' => '山田', 'gender' => 1, 'created_at' => '2026-01-15 09:00:00']);

        $this->assertSame([$matched->id], $this->idsFor([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-01-15',
        ]));
    }

    public function test_pagination_links_keep_search_query(): void
    {
        Contact::factory()->count(8)->create(['gender' => 1]);

        $this->get('/admin?gender=1')
            ->assertOk()
            ->assertSee('gender=1&amp;page=2', false);
    }

    public function test_invalid_gender_returns_validation_error(): void
    {
        $this->get('/admin?gender=9')->assertSessionHasErrors(['gender' => '性別の値が不正です']);
    }
}
