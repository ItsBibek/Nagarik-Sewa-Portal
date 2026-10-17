<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_id' => $this->tracking_id,
            'status' => $this->status,
            'submitted_at' => $this->created_at?->toIso8601String(),
            'service' => [
                'id' => $this->serviceType->id,
                'name' => $this->serviceType->name,
                'department' => $this->serviceType->department->name,
                'fee_npr' => (float) $this->serviceType->fee,
            ],
            'documents' => $this->whenLoaded(
                'documents',
                fn () => $this->documents->map(fn ($document): array => [
                    'type' => $document->document_type,
                    'uploaded_at' => $document->uploaded_at?->toIso8601String(),
                ])->values(),
            ),
            'status_history' => $this->whenLoaded(
                'statusHistories',
                fn () => $this->statusHistories->map(fn ($history): array => [
                    'status' => $history->new_status,
                    'changed_at' => $history->created_at?->toIso8601String(),
                ])->values(),
            ),
        ];
    }
}
