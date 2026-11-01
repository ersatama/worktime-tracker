<?php

namespace Tests\Feature;

use App\Exceptions\TimerException;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Services\TimerService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TimerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_timer_starts_with_a_task_description(): void
    {
        $this->travelTo('2026-11-02 09:00:00');
        $employee = Employee::factory()->create();

        $this->post("/employees/{$employee->id}/timer/start", ['description' => 'Подготовка отчёта'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $entry = $employee->timeEntries()->sole();
        $this->assertSame('Подготовка отчёта', $entry->description);
        $this->assertSame('2026-11-02 09:00:00', $entry->started_at->toDateTimeString());
        $this->assertTrue($entry->isRunning());
    }

    public function test_a_description_is_required(): void
    {
        $employee = Employee::factory()->create();

        $this->post("/employees/{$employee->id}/timer/start", ['description' => ''])
            ->assertSessionHasErrors(['description' => 'Заполните поле «описание задачи».']);

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_starting_a_running_timer_again_is_refused(): void
    {
        $employee = Employee::factory()->create();
        $this->post("/employees/{$employee->id}/timer/start", ['description' => 'Первая задача']);

        $this->post("/employees/{$employee->id}/timer/start", ['description' => 'Вторая задача'])
            ->assertRedirect()
            ->assertSessionHasErrors(['timer' => TimerException::alreadyRunning()->getMessage()]);

        $entry = $employee->timeEntries()->sole();
        $this->assertSame('Первая задача', $entry->description);
        $this->assertTrue($entry->isRunning());
    }

    public function test_starting_a_running_timer_again_returns_409_to_json_clients(): void
    {
        $employee = Employee::factory()->create();
        TimeEntry::factory()->for($employee)->running()->create();

        $this->postJson("/employees/{$employee->id}/timer/start", ['description' => 'Ещё раз'])
            ->assertStatus(409)
            ->assertJson(['message' => TimerException::alreadyRunning()->getMessage()]);
    }

    public function test_the_timer_stops_and_can_start_again(): void
    {
        $employee = Employee::factory()->create();

        $this->travelTo('2026-11-02 09:00:00');
        $this->post("/employees/{$employee->id}/timer/start", ['description' => 'Звонок с клиентом']);
        $this->travelTo('2026-11-02 09:25:30');
        $this->post("/employees/{$employee->id}/timer/stop")->assertSessionHasNoErrors();
        $this->travelTo('2026-11-02 09:30:00');
        $this->post("/employees/{$employee->id}/timer/start", ['description' => 'Письмо по итогам'])
            ->assertSessionHasNoErrors();

        [$first, $second] = $employee->timeEntries()->orderBy('id')->get();
        $this->assertSame('2026-11-02 09:25:30', $first->ended_at->toDateTimeString());
        $this->assertSame('Письмо по итогам', $second->description);
        $this->assertTrue($second->isRunning());
    }

    public function test_stopping_a_timer_that_is_not_running_is_refused(): void
    {
        $employee = Employee::factory()->create();
        TimeEntry::factory()->for($employee)->create();

        $this->post("/employees/{$employee->id}/timer/stop")
            ->assertSessionHasErrors(['timer' => TimerException::notRunning()->getMessage()]);
    }

    public function test_timers_of_different_employees_are_independent(): void
    {
        [$anna, $boris] = Employee::factory()->count(2)->create();

        $this->post("/employees/{$anna->id}/timer/start", ['description' => 'Задача Анны'])->assertSessionHasNoErrors();
        $this->post("/employees/{$boris->id}/timer/start", ['description' => 'Задача Бориса'])->assertSessionHasNoErrors();

        $this->assertSame(2, TimeEntry::running()->count());
    }

    public function test_the_database_refuses_a_second_running_timer(): void
    {
        $employee = Employee::factory()->create();
        TimeEntry::factory()->for($employee)->running()->create();

        // Even past the application's own check (e.g. two requests at once).
        $this->expectException(UniqueConstraintViolationException::class);
        TimeEntry::factory()->for($employee)->running()->create();
    }

    public function test_a_timer_being_changed_elsewhere_gives_a_clear_answer(): void
    {
        $employee = Employee::factory()->create();
        $lock = Cache::lock("timer:employee:{$employee->id}", 10);
        $lock->acquire();

        try {
            $this->expectExceptionObject(TimerException::busy());
            app(TimerService::class)->start($employee, 'Задача');
        } finally {
            $lock->release();
        }
    }

    public function test_an_unknown_employee_is_404(): void
    {
        $this->post('/employees/999/timer/start', ['description' => 'Задача'])->assertNotFound();
    }
}
