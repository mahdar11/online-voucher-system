<?php

namespace App\Modules\Identity\Http\Requests;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah staf (dengan email) atau ubah user (tanpa email).
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi oleh middleware permission:manage users
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'phone' => $this->filled('phone') ? preg_replace('/[\s-]/', '', (string) $this->input('phone')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->route('user') === null;

        return array_filter([
            'name' => ['required', 'string', 'max:255'],
            'email' => $creating ? ['required', 'email', 'max:255', Rule::unique(User::class, 'email')] : null,
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            // Undangan hanya untuk staf; role customer hanya lewat ubah user.
            'role' => ['required', Rule::in($creating ? Role::staffValues() : array_column(Role::cases(), 'value'))],
        ]);
    }

    public function role(): Role
    {
        return Role::from($this->validated('role'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone.regex' => 'Format nomor HP tidak valid, contoh: 081234567890.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => 'nama', 'phone' => 'nomor HP'];
    }
}
