<?php

namespace App\Http\Resources;

use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimeEntry */
class TimeEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'description' => $this->description,
            'started_at' => $this->started_at->utc()->format(DATE_RFC3339_EXTENDED),
            'ended_at' => $this->ended_at?->utc()->format(DATE_RFC3339_EXTENDED),
            'running' => $this->isRunning(),
        ];
    }
}
