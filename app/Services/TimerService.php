<?php

namespace App\Services;

use App\Exceptions\TimerException;
use App\Models\Employee;
use App\Models\TimeEntry;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;

class TimerService
{
    public function start(Employee $employee, string $description): TimeEntry
    {
        return $this->exclusively($employee, function () use ($employee, $description) {
            if ($employee->runningEntry()->exists()) {
                throw TimerException::alreadyRunning();
            }

            try {
                return $employee->timeEntries()->create([
                    'description' => $description,
                    'started_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // Another request got past the check first; the unique index on
                // running_employee_id is the final word.
                throw TimerException::alreadyRunning();
            }
        });
    }

    public function stop(Employee $employee): TimeEntry
    {
        return $this->exclusively($employee, function () use ($employee) {
            $entry = $employee->runningEntry()->first() ?? throw TimerException::notRunning();
            $entry->update(['ended_at' => now()]);

            return $entry;
        });
    }

    /**
     * One start/stop per employee at a time (a Redis lock in Docker), so a double
     * click or two open tabs get a clear answer instead of racing each other.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function exclusively(Employee $employee, Closure $callback): mixed
    {
        try {
            return Cache::lock("timer:employee:{$employee->id}", 10)->block(3, $callback);
        } catch (LockTimeoutException) {
            throw TimerException::busy();
        }
    }
}
