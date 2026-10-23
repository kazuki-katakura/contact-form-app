<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::pluck('id');
        $tagIds = Tag::pluck('id');

        Contact::factory()
            ->count(20)
            ->state(function () use ($categoryIds) {
                $postedAt = fake()->dateTimeBetween('-30 days', 'now');

                return [
                    'category_id' => $categoryIds->random(),
                    'created_at' => $postedAt,
                    'updated_at' => $postedAt,
                ];
            })
            ->create()
            ->each(function (Contact $contact) use ($tagIds) {
                $contact->tags()->attach($tagIds->random(rand(1, 3)));
            });
    }
}
