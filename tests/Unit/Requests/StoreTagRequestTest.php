<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTagRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreTagRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_passes_with_new_name(): void
    {
        $this->assertTrue($this->validate(['name' => '新機能の要望'])->passes());
    }

    public function test_rejects_empty_name(): void
    {
        $validator = $this->validate(['name' => '']);

        $this->assertSame('タグ名を入力してください', $validator->errors()->first('name'));
    }

    public function test_name_allows_50_characters_and_rejects_51(): void
    {
        $this->assertTrue($this->validate(['name' => str_repeat('あ', 50)])->passes());

        $validator = $this->validate(['name' => str_repeat('あ', 51)]);
        $this->assertSame('タグ名は50文字以内で入力してください', $validator->errors()->first('name'));
    }

    public function test_rejects_duplicate_name(): void
    {
        Tag::factory()->create(['name' => '質問']);

        $validator = $this->validate(['name' => '質問']);

        $this->assertSame('そのタグ名は既に使用されています', $validator->errors()->first('name'));
    }
}
