<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Http\Rules\ExistingPackage;
use Illuminate\Foundation\Http\FormRequest;

class ImportVouchersRequest extends FormRequest
{
    public const MAX_FILE_KB = 2048;

    public function authorize(): bool
    {
        return true; // dibatasi oleh middleware permission:manage vouchers
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'package_id' => ['required', 'integer', new ExistingPackage],
            'file' => ['required', 'file', 'extensions:csv,txt', 'mimes:csv,txt', 'max:'.self::MAX_FILE_KB],
            'note' => ['nullable', 'string', 'max:255'],
            // Mencegah satu kode terjual dua kali: online dan lewat kartu fisik di toko.
            'confirm_withdrawn' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirm_withdrawn.accepted' => 'Pastikan kartu fisik untuk kode-kode ini sudah ditarik dari penjualan offline, lalu centang konfirmasinya.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'package_id' => 'paket',
            'file' => 'file CSV',
            'note' => 'catatan',
        ];
    }
}
