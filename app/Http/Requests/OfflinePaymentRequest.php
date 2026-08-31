<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfflinePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'amount_cents' => ['required', 'integer', 'min:1'],
            'paid_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
