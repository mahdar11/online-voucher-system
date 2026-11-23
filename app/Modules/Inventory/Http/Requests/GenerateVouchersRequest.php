<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Actions\GenerateVouchers;
use App\Modules\Inventory\Enums\PasswordMode;
use App\Modules\Inventory\Http\Rules\ExistingPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateVouchersRequest extends FormRequest
{
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
            'package_id' => ['required', 'integer', new ExistingPackage],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.GenerateVouchers::MAX_QUANTITY],
            'length' => ['required', 'integer', 'min:6', 'max:16'],
            'prefix' => ['nullable', 'string', 'max:8', 'regex:/^[A-Z0-9-]*$/'],
            'password_mode' => ['required', Rule::enum(PasswordMode::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function passwordMode(): PasswordMode
    {
        return PasswordMode::from($this->validated('password_mode'));
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
