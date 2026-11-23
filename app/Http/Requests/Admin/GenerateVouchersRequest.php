<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateVouchersRequest extends FormRequest
{
    public const MAX_QUANTITY = 1000;

    public const PASSWORD_MODES = ['none', 'same', 'random'];

    public function authorize(): bool
    {
        return true; // dibatasi oleh middleware permission:manage vouchers
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prefix' => $this->filled('prefix') ? strtoupper(trim((string) $this->input('prefix'))) : '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', Rule::exists('voucher_packages', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY],
            'length' => ['required', 'integer', 'min:6', 'max:16'],
            'prefix' => ['nullable', 'string', 'max:8', 'regex:/^[A-Z0-9-]*$/'],
            'password_mode' => ['required', Rule::in(self::PASSWORD_MODES)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prefix.regex' => 'Prefix hanya boleh huruf, angka, dan tanda hubung.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'package_id' => 'paket',
            'quantity' => 'jumlah',
            'length' => 'panjang kode',
            'password_mode' => 'password',
            'note' => 'catatan',
        ];
    }
}
