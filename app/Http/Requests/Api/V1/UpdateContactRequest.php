<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Concerns\ContactRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    use ContactRules;

    public function authorize(): bool
    {
        return true;
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
