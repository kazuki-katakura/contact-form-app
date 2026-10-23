<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_has_many_contacts(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();
        Contact::factory()->count(3)->for($category)->create();
        Contact::factory()->for($otherCategory)->create();

        $this->assertCount(3, $category->contacts);
        $this->assertContainsOnlyInstancesOf(Contact::class, $category->contacts);
        $this->assertTrue($category->contacts->every(fn (Contact $contact) => $contact->category_id === $category->id));
    }

    public function test_deleting_category_cascades_to_contacts(): void
    {
        $category = Category::factory()->create();
        Contact::factory()->count(2)->for($category)->create();

        $category->delete();

        $this->assertDatabaseCount('contacts', 0);
    }
}
