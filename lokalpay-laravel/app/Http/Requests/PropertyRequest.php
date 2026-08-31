<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PropertyRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:200'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'purchase_cost_cents' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
