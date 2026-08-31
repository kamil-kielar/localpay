<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ObligationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'lease_id' => ['required', 'uuid'],
            'period' => ['required', 'date_format:Y-m'],
            'due_date' => ['required', 'date'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
