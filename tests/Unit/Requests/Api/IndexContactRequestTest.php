<?php

namespace Tests\Unit\Requests\Api;

use App\Http\Requests\Api\V1\IndexContactRequest;
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

    public function test_accepts_all_valid_filters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate([
            'keyword' => '田中',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-01-15',
            'page' => 2,
            'per_page' => 100,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_gender_zero_which_is_only_valid_on_web(): void
    {
        $validator = $this->validate(['gender' => 0]);

        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_rejects_out_of_range_gender(): void
    {
        $this->assertSame('性別の値が不正です', $this->validate(['gender' => 4])->errors()->first('gender'));
    }

    public function test_rejects_unknown_category_and_invalid_date(): void
    {
        $validator = $this->validate(['category_id' => 999, 'date' => 'yesterday-ish']);

        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
        $this->assertTrue($validator->errors()->has('date'));
    }

    /**
     * @dataProvider invalidPaginationProvider
     */
    public function test_rejects_invalid_pagination(array $data, string $field): void
    {
        $this->assertTrue($this->validate($data)->errors()->has($field));
    }

    public static function invalidPaginationProvider(): array
    {
        return [
            'per_page zero' => [['per_page' => 0], 'per_page'],
            'per_page over 100' => [['per_page' => 101], 'per_page'],
            'per_page not integer' => [['per_page' => 'ten'], 'per_page'],
            'page zero' => [['page' => 0], 'page'],
        ];
    }

    public function test_rejects_too_long_keyword(): void
    {
        $this->assertTrue($this->validate(['keyword' => str_repeat('a', 256)])->errors()->has('keyword'));
    }
}
