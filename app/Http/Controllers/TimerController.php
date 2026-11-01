<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartTimerRequest;
use App\Models\Employee;
use App\Services\TimerService;
use Illuminate\Http\RedirectResponse;

/** A TimerException (already running / not running) becomes a form error, see bootstrap/app.php. */
class TimerController extends Controller
{
    public function start(StartTimerRequest $request, Employee $employee, TimerService $timer): RedirectResponse
    {
        $timer->start($employee, $request->validated('description'));

        return back()->with('success', "Таймер запущен: {$employee->name}.");
    }

    public function stop(Employee $employee, TimerService $timer): RedirectResponse
    {
        $timer->stop($employee);

        return back()->with('success', "Таймер остановлен: {$employee->name}.");
    }
}
