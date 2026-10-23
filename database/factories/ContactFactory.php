<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    private const DETAILS = [
        '注文した商品がまだ届いていません。配送状況を確認していただけますか。',
        '届いた商品のサイズが合わなかったため、交換をお願いしたいです。',
        '商品に傷がついていました。返品の手続きについて教えてください。',
        'お届け日時の変更は可能でしょうか。',
        'ギフト包装に対応しているか知りたいです。',
        '店舗の営業時間と定休日を教えてください。',
        '注文確認メールが届きません。登録したアドレスを確認してもらえますか。',
        '色違いの在庫が入荷する予定はありますか。',
        '支払い方法に後払いを追加してほしいです。',
        'サイトの表示が崩れて購入ボタンが押せませんでした。',
    ];

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'first_name' => fake()->lastName(),
            'last_name' => fake()->firstName(),
            'gender' => fake()->randomElement(array_keys(Contact::GENDERS)),
            'email' => fake()->unique()->safeEmail(),
            'tel' => fake()->numerify('0##########'),
            'address' => fake()->prefecture().fake()->city().fake()->ward().fake()->streetAddress(),
            'building' => fake()->optional(0.7)->secondaryAddress(),
            'detail' => fake()->randomElement(self::DETAILS),
        ];
    }
}
