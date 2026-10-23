<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ContactRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    use ContactRules;

    /**
     * バリデーション失敗時は入力ページに戻す（確認ページはPOST専用のため）。
     */
    protected $redirect = '/';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力ページは電話番号を3分割で送信するため、tel が空なら結合して補完する。
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('tel')) && $this->hasAny(['tel1', 'tel2', 'tel3'])) {
            $this->merge([
                'tel' => $this->input('tel1').$this->input('tel2').$this->input('tel3'),
            ]);
        }
    }

    public function rules(): array
    {
        return $this->contactRules();
    }

    public function messages(): array
    {
        return $this->contactMessages();
    }
}
