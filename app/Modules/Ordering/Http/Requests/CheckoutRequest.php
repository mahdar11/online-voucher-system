<?php

namespace App\Modules\Ordering\Http\Requests;

use App\Modules\Ordering\Actions\PlaceOrder;
use App\Modules\Ordering\Data\CustomerData;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // checkout terbuka untuk guest
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => $this->filled('phone') ? preg_replace('/[\s-]/', '', (string) $this->input('phone')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.PlaceOrder::MAX_QUANTITY],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
        ];
    }

    public function customer(): CustomerData
    {
        return new CustomerData(
            name: $this->validated('name'),
            email: $this->validated('email'),
            phone: $this->validated('phone'),
            userId: $this->user()?->getAuthIdentifier(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.max' => 'Maksimal '.PlaceOrder::MAX_QUANTITY.' voucher per pesanan.',
            'phone.regex' => 'Format nomor HP tidak valid, contoh: 081234567890.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'quantity' => 'jumlah',
            'phone' => 'nomor HP',
        ];
    }
}
