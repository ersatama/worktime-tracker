<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_is_added(): void
    {
        $this->post('/employees', ['name' => '  Анна Смирнова ', 'position' => ''])
            ->assertRedirect()
            ->assertSessionHas('success');

        $employee = Employee::sole();
        $this->assertSame('Анна Смирнова', $employee->name);
        $this->assertNull($employee->position);
    }

    public function test_a_name_is_required(): void
    {
        $this->post('/employees', ['name' => '   '])->assertSessionHasErrors('name');
        $this->post('/employees', ['name' => str_repeat('я', 101)])->assertSessionHasErrors('name');

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_the_list_shows_todays_time_and_the_running_timer(): void
    {
        $this->travelTo('2026-11-02 12:00:00');
        $boris = Employee::factory()->create(['name' => 'Борис']);
        $anna = Employee::factory()->create(['name' => 'Анна']);
        TimeEntry::factory()->for($anna)->create(['started_at' => '2026-11-02 09:00:00', 'ended_at' => '2026-11-02 10:30:00']);
        // Yesterday's work is not today's.
        TimeEntry::factory()->for($anna)->create(['started_at' => '2026-11-01 09:00:00', 'ended_at' => '2026-11-01 17:00:00']);
        $running = TimeEntry::factory()->for($boris)->running()->create([
            'description' => 'Ревью договора',
            'started_at' => '2026-11-02 11:45:00',
        ]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Employees/Index')
            ->has('employees', 2)
            ->where('employees.0.name', 'Анна')
            ->where('employees.0.today_seconds', 5400)
            ->where('employees.0.running', null)
            ->where('employees.1.name', 'Борис')
            ->where('employees.1.today_seconds', 900)
            ->where('employees.1.running.id', $running->id)
            ->where('employees.1.running.description', 'Ревью договора')
            ->where('employees.1.running.started_at', '2026-11-02T11:45:00.000+00:00')
            ->has('serverNow')
        );
    }

    public function test_an_empty_list_renders(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Employees/Index')
            ->has('employees', 0)
        );
    }
}
