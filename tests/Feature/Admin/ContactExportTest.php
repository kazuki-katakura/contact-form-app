<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    private const BOM = "\xEF\xBB\xBF";

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    private function csvRows(string $content): array
    {
        $this->assertStringStartsWith(self::BOM, $content);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, substr($content, strlen(self::BOM)));
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    public function test_exports_all_contacts_in_newest_order_with_header(): void
    {
        $category = Category::factory()->create(['content' => '商品トラブル']);
        $older = Contact::factory()->for($category)->create([
            'first_name' => '山田', 'last_name' => '太郎', 'gender' => 1, 'building' => null,
            'created_at' => '2026-01-10 09:00:00',
        ]);
        $newer = Contact::factory()->for($category)->create([
            'first_name' => '鈴木', 'last_name' => '花子', 'gender' => 2,
            'created_at' => '2026-01-12 18:30:00',
        ]);

        $response = $this->get('/contacts/export');

        $response->assertOk()->assertDownload();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $rows = $this->csvRows($response->streamedContent());
        $this->assertSame(['ID', '氏名', '性別', 'メールアドレス', '電話番号', '住所', '建物名', 'お問い合わせの種類', 'お問い合わせ内容', '作成日時'], $rows[0]);
        $this->assertCount(3, $rows);
        $this->assertSame([
            (string) $newer->id, '鈴木 花子', '女性', $newer->email, $newer->tel, $newer->address,
            $newer->building ?? '', '商品トラブル', $newer->detail, '2026-01-12 18:30:00',
        ], $rows[1]);
        $this->assertSame((string) $older->id, $rows[2][0]);
        $this->assertSame('男性', $rows[2][2]);
        $this->assertSame('', $rows[2][6]);
    }

    public function test_exports_only_contacts_matching_filters(): void
    {
        $category = Category::factory()->create();
        $matched = Contact::factory()->for($category)->create(['first_name' => '山田', 'gender' => 1, 'created_at' => '2026-02-01 10:00:00']);
        Contact::factory()->for($category)->create(['first_name' => '山田', 'gender' => 2, 'created_at' => '2026-02-01 10:00:00']);
        Contact::factory()->create(['first_name' => '田中', 'gender' => 1]);

        $response = $this->get('/contacts/export?'.http_build_query([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-02-01',
        ]));

        $rows = $this->csvRows($response->assertOk()->streamedContent());
        $this->assertCount(2, $rows);
        $this->assertSame((string) $matched->id, $rows[1][0]);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->get('/contacts/export?gender=7&category_id=999')
            ->assertSessionHasErrors([
                'gender' => '性別の値が不正です',
                'category_id' => '選択されたカテゴリーが存在しません',
            ]);
    }
}
