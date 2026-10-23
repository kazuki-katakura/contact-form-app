<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new IndexContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_passes_with_all_filters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate([
            'keyword' => '山田',
            'gender' => 2,
            'category_id' => $category->id,
            'date' => '2026-01-15',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_passes_without_filters(): void
    {
        $this->assertTrue($this->validate([])->passes());
    }

    /**
     * @dataProvider validGenderProvider
     */
    public function test_accepts_gender_from_zero_to_three(int $gender): void
    {
        $this->assertTrue($this->validate(['gender' => $gender])->passes());
    }

    public static function validGenderProvider(): array
    {
        return [[0], [1], [2], [3]];
    }

    public function test_rejects_invalid_gender(): void
    {
        $validator = $this->validate(['gender' => 5]);

        $this->assertTrue($validator->fails());
        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_rejects_unknown_category_invalid_date_and_long_keyword(): void
    {
        $validator = $this->validate([
            'keyword' => str_repeat('a', 256),
            'category_id' => 999,
            'date' => 'not-a-date',
        ]);

        $this->assertTrue($validator->errors()->has('keyword'));
        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
        $this->assertTrue($validator->errors()->has('date'));
    }
}
