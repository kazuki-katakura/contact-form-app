<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Concerns\ContactSearchRules;
use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
{
    use ContactSearchRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->searchRules('1,2,3'), [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    public function messages(): array
    {
        return array_merge($this->searchMessages(), [
            'page.integer' => 'ページ番号は整数で指定してください',
            'page.min' => 'ページ番号は1以上で指定してください',
            'per_page.integer' => '表示件数は整数で指定してください',
            'per_page.min' => '表示件数は1以上で指定してください',
            'per_page.max' => '表示件数は100以下で指定してください',
        ]);
    }
}
