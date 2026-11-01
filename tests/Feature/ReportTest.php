<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Monday.
        $this->travelTo('2026-11-02 15:00:00');
    }

    public function test_an_empty_report_lists_every_employee_with_zero_time(): void
    {
        Employee::factory()->create(['name' => 'Анна']);
        Employee::factory()->create(['name' => 'Борис']);

        $this->get('/reports?from=2026-10-01&to=2026-10-31')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->where('filters', ['from' => '2026-10-01', 'to' => '2026-10-31', 'employee' => null])
            ->has('totals', 2)
            ->where('totals.0.employee.name', 'Анна')
            ->where('totals.0.seconds', 0)
            ->where('totals.0.entries', 0)
            ->where('totals.1.seconds', 0)
            ->where('total_seconds', 0)
            ->has('entries', 0)
        );
    }

    public function test_a_report_without_employees_renders(): void
    {
        $this->get('/reports')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('totals', 0)
            ->has('entries', 0)
            ->where('total_seconds', 0)
        );
    }

    public function test_the_default_period_is_the_current_week(): void
    {
        $this->travelTo('2026-11-05 10:00:00'); // Thursday

        $this->get('/reports')->assertInertia(fn (Assert $page) => $page
            ->where('filters.from', '2026-11-02')
            ->where('filters.to', '2026-11-05')
        );
    }

    public function test_totals_and_entries_for_the_period(): void
    {
        $anna = Employee::factory()->create(['name' => 'Анна']);
        $boris = Employee::factory()->create(['name' => 'Борис']);
        $this->entry($anna, '2026-10-20 09:00', '2026-10-20 11:00'); // 2 h
        $this->entry($anna, '2026-10-21 13:00', '2026-10-21 13:30'); // 30 min
        $this->entry($boris, '2026-10-22 10:00', '2026-10-22 10:45'); // 45 min
        $this->entry($anna, '2026-09-30 09:00', '2026-09-30 18:00'); // outside the period

        $this->get('/reports?from=2026-10-01&to=2026-10-31')->assertInertia(fn (Assert $page) => $page
            ->where('totals.0.seconds', 9000)
            ->where('totals.0.entries', 2)
            ->where('totals.1.seconds', 2700)
            ->where('total_seconds', 11700)
            ->has('entries', 3)
            // Newest first, with the employee's name.
            ->where('entries.0.employee_name', 'Борис')
            ->where('entries.0.seconds', 2700)
            ->where('entries.2.started_at', '2026-10-20T09:00:00.000+00:00')
        );
    }

    public function test_the_report_can_be_narrowed_to_one_employee(): void
    {
        $anna = Employee::factory()->create(['name' => 'Анна']);
        $boris = Employee::factory()->create(['name' => 'Борис']);
        $this->entry($anna, '2026-11-02 09:00', '2026-11-02 10:00');
        $this->entry($boris, '2026-11-02 09:00', '2026-11-02 12:00');

        $this->get("/reports?from=2026-11-02&to=2026-11-02&employee={$boris->id}")->assertInertia(fn (Assert $page) => $page
            ->has('totals', 1)
            ->where('totals.0.employee.id', $boris->id)
            ->where('total_seconds', 10800)
            ->has('entries', 1)
            // The filter still offers every employee.
            ->has('employees', 2)
        );
    }

    public function test_entries_crossing_the_period_edges_count_only_their_part_inside(): void
    {
        $anna = Employee::factory()->create();
        $this->entry($anna, '2026-10-31 22:00', '2026-11-01 02:00'); // 2 h of it on Nov 1
        $this->entry($anna, '2026-11-01 23:00', '2026-11-02 01:00'); // 1 h of it on Nov 1

        $this->get('/reports?from=2026-11-01&to=2026-11-01')->assertInertia(fn (Assert $page) => $page
            ->where('total_seconds', 3 * 3600)
            ->where('entries.0.seconds', 3600)
            ->where('entries.1.seconds', 7200)
        );
    }

    public function test_a_running_timer_counts_up_to_now(): void
    {
        $anna = Employee::factory()->create();
        TimeEntry::factory()->for($anna)->running()->create(['started_at' => '2026-11-02 14:20:00']);

        $this->get('/reports?from=2026-11-02&to=2026-11-02')->assertInertia(fn (Assert $page) => $page
            ->where('total_seconds', 40 * 60)
            ->where('entries.0.running', true)
            ->where('entries.0.ended_at', null)
        );
    }

    public function test_a_running_timer_has_no_time_in_a_future_period(): void
    {
        $anna = Employee::factory()->create();
        TimeEntry::factory()->for($anna)->running()->create(['started_at' => '2026-11-02 14:20:00']);

        $this->get('/reports?from=2026-11-10&to=2026-11-12')->assertInertia(fn (Assert $page) => $page
            ->where('total_seconds', 0)
            ->has('entries', 0)
        );
    }

    public function test_days_follow_the_viewers_time_zone(): void
    {
        $anna = Employee::factory()->create();
        // 21:30 UTC on Nov 1 is already Nov 2 in Almaty (UTC+5).
        $this->entry($anna, '2026-11-01 21:30', '2026-11-01 22:00');

        $this->withUnencryptedCookie('tz', 'Asia/Almaty')
            ->get('/reports?from=2026-11-02&to=2026-11-02')
            ->assertInertia(fn (Assert $page) => $page
                ->where('timezone', 'Asia/Almaty')
                ->where('total_seconds', 1800)
            );
    }

    public function test_without_a_time_zone_cookie_days_follow_the_app_zone(): void
    {
        $anna = Employee::factory()->create();
        $this->entry($anna, '2026-11-01 21:30', '2026-11-01 22:00');

        $this->get('/reports?from=2026-11-02&to=2026-11-02')
            ->assertInertia(fn (Assert $page) => $page
                ->where('timezone', 'UTC')
                ->where('total_seconds', 0)
            );
    }

    public function test_an_unknown_time_zone_cookie_falls_back_to_the_app_zone(): void
    {
        $this->withUnencryptedCookie('tz', 'Mars/Olympus')
            ->get('/reports')
            ->assertInertia(fn (Assert $page) => $page->where('timezone', 'UTC'));
    }

    public function test_a_period_ending_before_it_starts_is_refused(): void
    {
        $this->get('/reports?from=2026-11-10&to=2026-11-01')
            ->assertRedirect('/reports')
            ->assertSessionHasErrors('to');
    }

    public function test_bad_filters_are_refused(): void
    {
        $this->get('/reports?from=01.11.2026')->assertRedirect('/reports')->assertSessionHasErrors('from');
        $this->get('/reports?employee=999')->assertRedirect('/reports')->assertSessionHasErrors('employee');
        $this->get('/reports?from=2025-01-01&to=2026-11-01')->assertRedirect('/reports')->assertSessionHasErrors('to');
    }

    private function entry(Employee $employee, string $start, string $end): TimeEntry
    {
        return TimeEntry::factory()->for($employee)->create(['started_at' => $start, 'ended_at' => $end]);
    }
}
