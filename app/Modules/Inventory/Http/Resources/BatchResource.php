<?php

namespace App\Modules\Inventory\Http\Resources;

use App\Modules\Inventory\Models\VoucherBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin VoucherBatch
 */
class BatchResource extends JsonResource
{
    /**
     * @param  array<int, string>  $packageNames
     * @param  array<int, string>  $creatorNames
     */
    public function __construct(
        VoucherBatch $batch,
        private readonly array $packageNames = [],
        private readonly array $creatorNames = [],
    ) {
        parent::__construct($batch);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'package' => $this->packageNames[$this->package_id] ?? null,
            'package_id' => $this->package_id,
            'source' => $this->source->value,
            'source_label' => $this->source->label(),
            'file_name' => $this->file_name,
            'quantity' => $this->quantity,
            'available_count' => (int) $this->available_count,
            'note' => $this->note,
            'creator' => $this->creatorNames[$this->created_by] ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
