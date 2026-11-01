<?php

namespace Database\Seeders;

use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/** Fictional employees and two weeks of their work, one of them busy right now. */
class DemoSeeder extends Seeder
{
    private const EMPLOYEES = [
        ['Анна Смирнова', 'Менеджер проектов'],
        ['Борис Ковалёв', 'Бэкенд-разработчик'],
        ['Вера Орлова', 'Дизайнер интерфейсов'],
        ['Глеб Никитин', 'Аналитик'],
        ['Дарья Лебедева', 'QA-инженер'],
    ];

    private const TASKS = [
        'Планёрка команды',
        'Разбор входящих заявок',
        'Подготовка коммерческого предложения',
        'Ревью макетов личного кабинета',
        'Созвон с клиентом «Северный ветер»',
        'Исправление ошибок в отчёте',
        'Написание документации по API',
        'Тестирование формы оплаты',
        'Анализ воронки регистрации',
        'Обновление дизайн-системы',
        'Настройка уведомлений',
        'Оценка задач на спринт',
    ];

    public function run(): void
    {
        if (Employee::query()->exists()) {
            $this->command?->warn('Сотрудники уже есть — демо-данные не добавлены.');

            return;
        }

        mt_srand(2026);
        // Working hours of a fictional office; stored in UTC like everything else.
        $tz = 'Europe/Moscow';
        $today = CarbonImmutable::now($tz)->startOfDay();

        foreach (self::EMPLOYEES as $index => [$name, $position]) {
            $employee = Employee::create(['name' => $name, 'position' => $position]);

            for ($daysAgo = 14; $daysAgo >= 1; $daysAgo--) {
                $day = $today->subDays($daysAgo);
                if ($day->isWeekend()) {
                    continue;
                }
                $cursor = $day->setTime(9, mt_rand(0, 3) * 15);
                for ($i = 0, $n = mt_rand(2, 4); $i < $n; $i++) {
                    $end = $cursor->addMinutes(mt_rand(3, 12) * 10);
                    $this->entry($employee, $cursor, $end);
                    $cursor = $end->addMinutes(mt_rand(1, 6) * 10);
                }
            }

            // Today: everyone did a bit, Борис is still at it.
            $start = CarbonImmutable::now($tz)->subMinutes(30 + $index * 25)->startOfMinute();
            $this->entry($employee, $start->subMinutes(90), $start->subMinutes(20));
            if ($index === 1) {
                $this->entry($employee, $start, null);
            }
        }
    }

    private function entry(Employee $employee, CarbonImmutable $start, ?CarbonImmutable $end): void
    {
        $employee->timeEntries()->create([
            'description' => self::TASKS[mt_rand(0, count(self::TASKS) - 1)],
            'started_at' => $start->utc(),
            'ended_at' => $end?->utc(),
        ]);
    }
}
