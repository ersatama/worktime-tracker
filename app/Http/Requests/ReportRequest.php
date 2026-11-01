<?php

namespace App\Http\Requests;

use App\Support\ClientTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReportRequest extends FormRequest
{
    public const MAX_DAYS = 366;

    /** Bad filters fall back to the default period (a GET page must not redirect to itself). */
    protected $redirectRoute = 'reports.index';

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'employee' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }

    public function attributes(): array
    {
        return ['from' => 'начало периода', 'to' => 'конец периода', 'employee' => 'сотрудник'];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                [$from, $to] = $this->period();
                if ($to->lt($from)) {
                    $validator->errors()->add('to', 'Конец периода не может быть раньше начала.');
                } elseif ($from->diffInDays($to) >= self::MAX_DAYS) {
                    $validator->errors()->add('to', 'Период не может быть длиннее '.self::MAX_DAYS.' дней.');
                }
            },
        ];
    }

    public function timezone(): string
    {
        return ClientTimezone::from($this);
    }

    /**
     * The chosen days in the viewer's time zone; by default the current week up to today.
     *
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    public function period(): array
    {
        $tz = $this->timezone();
        $today = CarbonImmutable::now($tz)->startOfDay();
        $day = fn (?string $value) => $value ? CarbonImmutable::createFromFormat('!Y-m-d', $value, $tz) : null;

        return [
            $day($this->input('from')) ?? $today->startOfWeek(),
            $day($this->input('to')) ?? $today,
        ];
    }
}
