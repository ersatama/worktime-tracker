<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory;

    protected $fillable = ['description', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @param Builder<TimeEntry> $query */
    public function scopeRunning(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /**
     * Entries that overlap [$from, $to): started before the period ends and
     * not finished before it starts (a running entry has no end yet).
     *
     * @param  Builder<TimeEntry>  $query
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->where('started_at', '<', $to)
            ->where(fn (Builder $q) => $q->whereNull('ended_at')->orWhere('ended_at', '>', $from));
    }

    public function isRunning(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Seconds of this entry inside [$from, $to); a running entry counts up to $now.
     * Null when the entry does not reach into the window at all.
     */
    public function secondsWithin(CarbonInterface $from, CarbonInterface $to, CarbonInterface $now): ?int
    {
        $start = $this->started_at->max($from);
        $end = ($this->ended_at ?? $now)->min($to);

        return $end->lt($start) ? null : (int) $start->diffInSeconds($end);
    }
}
