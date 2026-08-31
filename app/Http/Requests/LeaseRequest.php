<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'property_id' => ['required', 'uuid'],
            'tenant_name' => ['required', 'string', 'max:120'],
            'tenant_email' => ['nullable', 'email:rfc', 'max:255', 'required_without:tenant_phone'],
            'tenant_phone' => ['nullable', 'string', 'max:30', 'required_without:tenant_email'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'monthly_rent_cents' => ['required', 'integer', 'min:1'],
            'due_day' => ['required', 'integer', 'between:1,28'],
            'status' => ['sometimes', 'in:draft,active,ended'],
        ];
    }
}
