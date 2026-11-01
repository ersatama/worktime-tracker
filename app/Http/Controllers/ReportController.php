<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\TimeEntryResource;
use App\Models\Employee;
use App\Services\WorkTimeReport;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(ReportRequest $request, WorkTimeReport $report): Response
    {
        [$from, $to] = $request->period();
        $employees = Employee::query()->orderBy('name')->orderBy('id')->get();
        $employeeId = $request->integer('employee') ?: null;
        $selected = $employeeId ? $employees->where('id', $employeeId)->values() : $employees;

        $result = $report->build($selected, $from, $to);
        $names = $employees->pluck('name', 'id');

        return Inertia::render('Reports/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'employee' => $employeeId,
            ],
            'timezone' => $request->timezone(),
            'employees' => EmployeeResource::collection($employees)->resolve(),
            'totals' => array_map(fn (array $row) => [
                'employee' => (new EmployeeResource($row['employee']))->resolve(),
                'seconds' => $row['seconds'],
                'entries' => $row['entries'],
            ], $result['totals']),
            'total_seconds' => $result['total_seconds'],
            'entries' => array_map(fn (array $row) => [
                ...(new TimeEntryResource($row['entry']))->resolve(),
                'employee_name' => $names[$row['entry']->employee_id],
                'seconds' => $row['seconds'],
            ], $result['entries']),
        ]);
    }
}
