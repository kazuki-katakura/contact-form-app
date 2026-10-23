<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactConfirmTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::factory()->create(['content' => '商品の交換について']);
    }

    public function test_confirm_page_shows_input_values_with_category_and_tag_names(): void
    {
        $tags = Tag::factory()->count(2)->sequence(['name' => '質問'], ['name' => '要望'])->create();

        $response = $this->post('/contacts/confirm', $this->validContactData([
            'gender' => 2,
            'category_id' => $this->category->id,
            'tag_ids' => $tags->pluck('id')->all(),
        ]));

        $response->assertOk()
            ->assertViewIs('contact.confirm')
            ->assertSee('山田 太郎')
            ->assertSee('女性')
            ->assertSee('taro@example.com')
            ->assertSee('09012345678')
            ->assertSee('東京都渋谷区千駄ヶ谷1-2-3')
            ->assertSee('千駄ヶ谷マンション101')
            ->assertSee('商品の交換について')
            ->assertSee('質問, 要望')
            ->assertSee('商品の配送日について教えてください。');
    }

    public function test_split_tel_fields_are_combined_when_tel_is_empty(): void
    {
        $data = $this->validContactData(['category_id' => $this->category->id, 'tel' => '']);
        $data += ['tel1' => '080', 'tel2' => '1234', 'tel3' => '5678'];

        $this->post('/contacts/confirm', $data)
            ->assertOk()
            ->assertViewHas('validated', fn (array $validated) => $validated['tel'] === '08012345678');
    }

    public function test_validation_error_redirects_back_to_form_with_errors(): void
    {
        $response = $this->from('/')->post('/contacts/confirm', [
            'email' => 'invalid-email',
            'detail' => str_repeat('あ', 121),
        ]);

        $response->assertRedirect('/')
            ->assertSessionHasErrors([
                'first_name' => '姓を入力してください',
                'last_name' => '名を入力してください',
                'gender' => '性別を選択してください',
                'email' => 'メールアドレスはメール形式で入力してください',
                'tel' => '電話番号を入力してください',
                'address' => '住所を入力してください',
                'category_id' => 'お問い合わせの種類を選択してください',
                'detail' => 'お問い合わせ内容は120文字以内で入力してください',
            ]);
    }

    public function test_old_input_is_kept_after_validation_error(): void
    {
        $this->post('/contacts/confirm', ['first_name' => '山田'])
            ->assertRedirect('/');

        $this->get('/')->assertSee('value="山田"', false);
    }
}
