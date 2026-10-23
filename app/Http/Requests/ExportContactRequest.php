<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ContactSearchRules;
use Illuminate\Foundation\Http\FormRequest;

class ExportContactRequest extends FormRequest
{
    use ContactSearchRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->searchRules('0,1,2,3');
    }

    public function messages(): array
    {
        return $this->searchMessages();
    }
}
