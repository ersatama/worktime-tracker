<script setup>
import { computed, reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { formatDateTime, formatDuration, formatTime, isoDay } from '../../lib/format';
import { useNow } from '../../lib/useNow';

const props = defineProps({
    filters: { type: Object, required: true },
    timezone: { type: String, required: true },
    employees: { type: Array, required: true },
    totals: { type: Array, required: true },
    total_seconds: { type: Number, required: true },
    entries: { type: Array, required: true },
});

const page = usePage();
const now = useNow();
const errors = computed(() => Object.values(page.props.errors ?? {}));

const filters = reactive({ ...props.filters });

function apply() {
    router.get(
        '/reports',
        { from: filters.from, to: filters.to, employee: filters.employee || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const today = new Date();
const presets = [
    { label: 'Сегодня', from: today, to: today },
    {
        label: 'Неделя',
        from: new Date(today.getFullYear(), today.getMonth(), today.getDate() - ((today.getDay() + 6) % 7)),
        to: today,
    },
    { label: 'Месяц', from: new Date(today.getFullYear(), today.getMonth(), 1), to: today },
    {
        label: 'Прошлый месяц',
        from: new Date(today.getFullYear(), today.getMonth() - 1, 1),
        to: new Date(today.getFullYear(), today.getMonth(), 0),
    },
].map((p) => ({ ...p, from: isoDay(p.from), to: isoDay(p.to) }));

function usePreset(preset) {
    filters.from = preset.from;
    filters.to = preset.to;
    apply();
}

// Running entries were counted up to serverNow; add what has ticked since.
const ticked = computed(() => Math.max(0, (now.value - Date.parse(page.props.serverNow)) / 1000));
const runningBy = computed(() => {
    const counts = {};
    for (const e of props.entries) if (e.running) counts[e.employee_id] = (counts[e.employee_id] ?? 0) + 1;
    return counts;
});
const seconds = (entry) => entry.seconds + (entry.running ? ticked.value : 0);
const employeeSeconds = (row) => row.seconds + (runningBy.value[row.employee.id] ?? 0) * ticked.value;
const grandTotal = computed(() => props.total_seconds + Object.values(runningBy.value).reduce((a, b) => a + b, 0) * ticked.value);
const maxSeconds = computed(() => Math.max(1, ...props.totals.map(employeeSeconds)));

const period = computed(() => {
    const fmt = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
    const from = fmt.format(new Date(`${props.filters.from}T00:00`));
    const to = fmt.format(new Date(`${props.filters.to}T00:00`));
    return from === to ? from : `${from} — ${to}`;
});
</script>

<template>
    <Head title="Отчёт" />

    <h1 class="text-2xl font-semibold tracking-tight">Отчёт</h1>

    <form class="mt-6 grid gap-3 sm:grid-cols-[1fr_1fr_1.4fr]" @submit.prevent="apply">
        <label>
            <span class="label">С</span>
            <input v-model="filters.from" type="date" class="input" required :max="filters.to" @change="apply" />
        </label>
        <label>
            <span class="label">По</span>
            <input v-model="filters.to" type="date" class="input" required :min="filters.from" @change="apply" />
        </label>
        <label>
            <span class="label">Сотрудник</span>
            <select v-model="filters.employee" class="input" @change="apply">
                <option :value="null">Все сотрудники</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
            </select>
        </label>
    </form>

    <div class="mt-3 flex flex-wrap gap-1.5">
        <button
            v-for="preset in presets"
            :key="preset.label"
            type="button"
            class="cursor-pointer rounded-full border px-3 py-1 text-xs transition"
            :class="filters.from === preset.from && filters.to === preset.to
                ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900'
                : 'border-zinc-200 text-zinc-600 hover:border-zinc-400 dark:border-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600'"
            @click="usePreset(preset)"
        >
            {{ preset.label }}
        </button>
    </div>

    <div v-if="errors.length" class="mt-4 rounded-md border border-red-200 px-3 py-2 text-sm text-red-600 dark:border-red-500/30 dark:text-red-400">
        <p v-for="error in errors" :key="error">{{ error }}</p>
        <p class="mt-1 text-xs muted">Показан период по умолчанию.</p>
    </div>

    <section class="mt-10">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
            <p class="text-sm muted">{{ period }}</p>
            <p class="text-sm muted">Итого <span class="ml-1 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ formatDuration(grandTotal) }}</span></p>
        </div>

        <ul v-if="totals.length" class="mt-4 divide-y divide-hairline hairline border-y">
            <li v-for="row in totals" :key="row.employee.id" class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-2 py-3 sm:grid-cols-[minmax(0,14rem)_1fr_auto]">
                <div class="min-w-0">
                    <div class="truncate text-sm font-medium">{{ row.employee.name }}</div>
                    <div class="text-xs muted">{{ row.entries }} {{ row.entries === 1 ? 'запись' : row.entries >= 2 && row.entries <= 4 ? 'записи' : 'записей' }}</div>
                </div>
                <div class="order-last col-span-2 h-1 overflow-hidden rounded-full bg-zinc-100 sm:order-none sm:col-span-1 dark:bg-zinc-900">
                    <div class="h-full rounded-full bg-zinc-900 transition-[width] dark:bg-zinc-100" :style="{ width: `${(employeeSeconds(row) / maxSeconds) * 100}%` }" />
                </div>
                <div class="text-right text-sm tabular-nums" :class="{ muted: !employeeSeconds(row) }">{{ formatDuration(employeeSeconds(row)) }}</div>
            </li>
        </ul>
        <p v-else class="mt-4 text-sm muted">Сотрудников пока нет.</p>
    </section>

    <section class="mt-12">
        <h2 class="text-lg font-semibold tracking-tight">Записи</h2>

        <div v-if="entries.length" class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[36rem] text-sm">
                <thead>
                    <tr class="border-b hairline text-left text-xs muted">
                        <th class="py-2 pr-4 font-normal">Сотрудник</th>
                        <th class="py-2 pr-4 font-normal">Задача</th>
                        <th class="py-2 pr-4 font-normal">Время</th>
                        <th class="py-2 text-right font-normal">Длительность</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-hairline hairline">
                    <tr v-for="entry in entries" :key="entry.id">
                        <td class="py-2.5 pr-4 whitespace-nowrap">{{ entry.employee_name }}</td>
                        <td class="max-w-64 truncate py-2.5 pr-4" :title="entry.description">{{ entry.description }}</td>
                        <td class="py-2.5 pr-4 whitespace-nowrap muted">
                            {{ formatDateTime(entry.started_at) }} –
                            <template v-if="entry.ended_at">{{ formatTime(entry.ended_at) }}</template>
                            <span v-else class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                <span class="size-1.5 animate-pulse rounded-full bg-current" />идёт
                            </span>
                        </td>
                        <td class="py-2.5 text-right whitespace-nowrap tabular-nums">{{ formatDuration(seconds(entry)) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="mt-4 rounded-lg border border-dashed border-zinc-200 py-12 text-center dark:border-zinc-800">
            <p class="font-medium">За этот период записей нет</p>
            <p class="mt-1 text-sm muted">Выберите другой период или запустите таймер на странице сотрудников.</p>
        </div>

        <p class="mt-4 text-xs muted">
            Записи на стыке периода считаются только своей частью внутри него. Часовой пояс: {{ timezone }}.
        </p>
    </section>
</template>
