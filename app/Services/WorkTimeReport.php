<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Worked time per employee over a period of whole days in a given time zone.
 * Entries crossing the period's edges count only their part inside it, and a
 * running timer counts up to now.
 */
class WorkTimeReport
{
    /**
     * @param  Collection<int, Employee>  $employees  employees to report on (all of them get a row, even with zero time)
     * @return array{totals: list<array{employee: Employee, seconds: int, entries: int}>, total_seconds: int, entries: list<array{entry: TimeEntry, seconds: int}>}
     */
    public function build(
        Collection $employees,
        CarbonImmutable $fromDay,
        CarbonImmutable $toDay,
        ?CarbonImmutable $now = null,
    ): array {
        $now ??= CarbonImmutable::now();
        // [from, to) in UTC, which is how the timestamps are stored.
        $from = $fromDay->startOfDay()->utc();
        $to = $toDay->addDay()->startOfDay()->utc();

        $entries = TimeEntry::query()
            ->whereIn('employee_id', $employees->modelKeys())
            ->overlapping($from, $to)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (TimeEntry $entry) => [
                'entry' => $entry,
                'seconds' => $entry->secondsWithin($from, $to, $now),
            ])
            // A running timer overlaps a future period on paper but has no time in it yet.
            ->reject(fn (array $row) => $row['seconds'] === null)
            ->values();

        $byEmployee = $entries->groupBy(fn (array $row) => $row['entry']->employee_id);

        $totals = $employees->map(fn (Employee $employee) => [
            'employee' => $employee,
            'seconds' => (int) $byEmployee->get($employee->id, collect())->sum('seconds'),
            'entries' => $byEmployee->get($employee->id, collect())->count(),
        ])->values();

        return [
            'totals' => $totals->all(),
            'total_seconds' => (int) $totals->sum('seconds'),
            'entries' => $entries->all(),
        ];
    }
}
