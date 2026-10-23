<?php

namespace Tests\Unit\Requests\Api;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create();
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_accepts_all_required_fields_and_tags(): void
    {
        $tags = Tag::factory()->count(3)->create();

        $validator = $this->validate($this->validContactData([
            'category_id' => $this->category->id,
            'tag_ids' => $tags->pluck('id')->all(),
        ]));

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_missing_required_fields(): void
    {
        $validator = $this->validate([]);

        $this->assertEqualsCanonicalizing(
            ['first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'category_id', 'detail'],
            $validator->errors()->keys()
        );
    }

    public function test_rejects_invalid_values_with_japanese_messages(): void
    {
        $validator = $this->validate($this->validContactData([
            'gender' => 9,
            'email' => 'invalid',
            'tel' => '03-1234-5678',
            'category_id' => 999,
            'detail' => str_repeat('あ', 121),
            'tag_ids' => [999],
        ]));

        $errors = $validator->errors();
        $this->assertSame('性別の値が不正です', $errors->first('gender'));
        $this->assertSame('メールアドレスはメール形式で入力してください', $errors->first('email'));
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $errors->first('tel'));
        $this->assertSame('選択されたカテゴリーが存在しません', $errors->first('category_id'));
        $this->assertSame('お問い合わせ内容は120文字以内で入力してください', $errors->first('detail'));
        $this->assertSame('選択されたタグが存在しません', $errors->first('tag_ids.0'));
    }

    public function test_rejects_tag_ids_that_is_not_an_array(): void
    {
        $validator = $this->validate($this->validContactData([
            'category_id' => $this->category->id,
            'tag_ids' => 'abc',
        ]));

        $this->assertTrue($validator->errors()->has('tag_ids'));
    }

    public function test_update_request_uses_same_rules_as_store_request(): void
    {
        $this->assertEquals((new StoreContactRequest)->rules(), (new UpdateContactRequest)->rules());
        $this->assertEquals((new StoreContactRequest)->messages(), (new UpdateContactRequest)->messages());
    }
}
