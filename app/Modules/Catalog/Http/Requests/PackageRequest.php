<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi untuk tambah dan ubah paket voucher.
 */
class PackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi oleh middleware permission:manage packages
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'speed_limit' => $this->filled('speed_limit') ? strtoupper(trim((string) $this->input('speed_limit'))) : null,
            'quota_mb' => $this->filled('quota_mb') ? $this->input('quota_mb') : null,
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0) ?? 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:525600'],
            'quota_mb' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'speed_limit' => ['nullable', 'string', 'max:50', 'regex:/^\d+[KMG]?\/\d+[KMG]?$/'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'speed_limit.regex' => 'Format kecepatan harus upload/download, contoh: 5M/5M atau 512K/1M.',
            'duration_minutes.max' => 'Durasi maksimal 1 tahun.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama paket',
            'description' => 'deskripsi',
            'price' => 'harga',
            'duration_minutes' => 'durasi',
            'quota_mb' => 'kuota',
            'speed_limit' => 'kecepatan',
            'sort_order' => 'urutan',
        ];
    }
}
