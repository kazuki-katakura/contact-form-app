<?php

namespace App\Http\Requests\Concerns;

trait ContactSearchRules
{
    protected function searchRules(string $genderValues): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'integer', 'in:'.$genderValues],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'date' => ['nullable', 'date'],
        ];
    }

    protected function searchMessages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください',
            'keyword.max' => 'キーワードは255文字以内で入力してください',
            'gender.integer' => '性別の値が不正です',
            'gender.in' => '性別の値が不正です',
            'category_id.integer' => '選択されたカテゴリーが存在しません',
            'category_id.exists' => '選択されたカテゴリーが存在しません',
            'date.date' => '日付の形式が不正です',
        ];
    }
}
