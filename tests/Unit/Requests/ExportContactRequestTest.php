<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new ExportContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_accepts_valid_filters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validate([
            'keyword' => 'example.com',
            'gender' => 0,
            'category_id' => $category->id,
            'date' => '2026-10-01',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_invalid_gender(): void
    {
        $validator = $this->validate(['gender' => 4]);

        $this->assertSame('性別の値が不正です', $validator->errors()->first('gender'));
    }

    public function test_rejects_unknown_category_id(): void
    {
        $validator = $this->validate(['category_id' => 999]);

        $this->assertSame('選択されたカテゴリーが存在しません', $validator->errors()->first('category_id'));
    }

    public function test_rejects_invalid_date(): void
    {
        $this->assertTrue($this->validate(['date' => '2026-13-40'])->fails());
    }
}
