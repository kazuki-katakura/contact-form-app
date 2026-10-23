<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreContactRequest;
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

    public function test_passes_with_all_required_fields_and_tags(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $validator = $this->validate($this->validContactData([
            'category_id' => $this->category->id,
            'tag_ids' => $tags->pluck('id')->all(),
        ]));

        $this->assertTrue($validator->passes());
    }

    public function test_passes_without_building_and_tags(): void
    {
        $validator = $this->validate($this->validContactData([
            'category_id' => $this->category->id,
            'building' => null,
            'tag_ids' => null,
        ]));

        $this->assertTrue($validator->passes());
    }

    public function test_rejects_missing_required_fields_with_messages(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertSame('姓を入力してください', $errors->first('first_name'));
        $this->assertSame('名を入力してください', $errors->first('last_name'));
        $this->assertSame('性別を選択してください', $errors->first('gender'));
        $this->assertSame('メールアドレスを入力してください', $errors->first('email'));
        $this->assertSame('電話番号を入力してください', $errors->first('tel'));
        $this->assertSame('住所を入力してください', $errors->first('address'));
        $this->assertSame('お問い合わせの種類を選択してください', $errors->first('category_id'));
        $this->assertSame('お問い合わせ内容を入力してください', $errors->first('detail'));
    }

    /**
     * @dataProvider invalidTelProvider
     */
    public function test_rejects_invalid_tel_format(string $tel): void
    {
        $validator = $this->validate($this->validContactData([
            'category_id' => $this->category->id,
            'tel' => $tel,
        ]));

        $this->assertTrue($validator->fails());
        $this->assertSame('電話番号はハイフンなしの10〜11桁で入力してください', $validator->errors()->first('tel'));
    }

    public static function invalidTelProvider(): array
    {
        return [
            'with hyphens' => ['090-1234-5678'],
            '9 digits' => ['090123456'],
            '12 digits' => ['090123456789'],
            'contains letter' => ['0901234567a'],
        ];
    }

    public function test_rejects_invalid_email_format(): void
    {
        $validator = $this->validate($this->validContactData([
            'category_id' => $this->category->id,
            'email' => 'not-an-email',
        ]));

        $this->assertSame('メールアドレスはメール形式で入力してください', $validator->errors()->first('email'));
    }

    public function test_detail_allows_120_characters_and_rejects_121(): void
    {
        $base = $this->validContactData(['category_id' => $this->category->id]);

        $this->assertTrue($this->validate(array_merge($base, ['detail' => str_repeat('あ', 120)]))->passes());

        $validator = $this->validate(array_merge($base, ['detail' => str_repeat('あ', 121)]));
        $this->assertSame('お問い合わせ内容は120文字以内で入力してください', $validator->errors()->first('detail'));
    }

    public function test_rejects_invalid_gender_and_unknown_category_or_tag(): void
    {
        $validator = $this->validate($this->validContactData([
            'gender' => 4,
            'category_id' => 999,
            'tag_ids' => [999],
        ]));

        $errors = $validator->errors();
        $this->assertSame('性別の値が不正です', $errors->first('gender'));
        $this->assertSame('選択されたカテゴリーが存在しません', $errors->first('category_id'));
        $this->assertSame('選択されたタグが存在しません', $errors->first('tag_ids.0'));
    }
}
