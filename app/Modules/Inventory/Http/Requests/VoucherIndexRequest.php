<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Enums\VoucherStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filter daftar stok voucher dari query string.
 */
class VoucherIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'package' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(VoucherStatus::class)],
            'search' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array{package: string, status: string, search: string}
     */
    public function filters(): array
    {
        return [
            'package' => (string) ($this->validated('package') ?? ''),
            'status' => (string) ($this->validated('status') ?? ''),
            'search' => (string) ($this->validated('search') ?? ''),
        ];
    }
}
