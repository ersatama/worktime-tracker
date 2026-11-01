<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\TimeEntryResource;
use App\Models\Employee;
use App\Services\WorkTimeReport;
use App\Support\ClientTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request, WorkTimeReport $report): Response
    {
        $employees = Employee::query()->with('runningEntry')->orderBy('name')->orderBy('id')->get();
        $today = CarbonImmutable::now(ClientTimezone::from($request));
        $todaySeconds = collect($report->build($employees, $today, $today)['totals'])
            ->mapWithKeys(fn (array $row) => [$row['employee']->id => $row['seconds']]);

        return Inertia::render('Employees/Index', [
            'employees' => $employees->map(fn (Employee $employee) => [
                ...(new EmployeeResource($employee))->resolve(),
                'today_seconds' => $todaySeconds[$employee->id],
                'running' => $employee->runningEntry
                    ? (new TimeEntryResource($employee->runningEntry))->resolve()
                    : null,
            ]),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return back()->with('success', "Сотрудник «{$employee->name}» добавлен.");
    }
}
