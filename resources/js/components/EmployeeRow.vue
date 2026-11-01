<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { formatClock, formatDuration, formatTime } from '../lib/format';

const props = defineProps({
    employee: { type: Object, required: true },
    // Server time in ms, ticking.
    now: { type: Number, required: true },
    serverNow: { type: String, required: true },
});

const start = useForm({ description: '' });
const stop = useForm({});

const running = computed(() => props.employee.running);
const elapsed = computed(() => (running.value ? (props.now - Date.parse(running.value.started_at)) / 1000 : 0));
// today_seconds was counted up to serverNow; add what has ticked since.
const today = computed(() =>
    props.employee.today_seconds + (running.value ? Math.max(0, (props.now - Date.parse(props.serverNow)) / 1000) : 0),
);
const error = computed(() => start.errors.description || start.errors.timer || stop.errors.timer);

function startTimer() {
    start.post(`/employees/${props.employee.id}/timer/start`, {
        preserveScroll: true,
        onSuccess: () => start.reset(),
    });
}

function stopTimer() {
    stop.post(`/employees/${props.employee.id}/timer/stop`, { preserveScroll: true });
}
</script>

<template>
    <li class="grid grid-cols-[minmax(0,1fr)_auto] gap-x-4 gap-y-3 py-4 sm:grid-cols-[minmax(0,1fr)_6.5rem_minmax(0,1.6fr)] sm:items-center">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <span
                    class="size-1.5 shrink-0 rounded-full"
                    :class="running ? 'animate-pulse bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-700'"
                    :title="running ? 'Работает' : 'Не работает'"
                />
                <span class="truncate font-medium">{{ employee.name }}</span>
            </div>
            <div class="mt-0.5 truncate pl-3.5 text-sm muted">{{ employee.position || 'Без должности' }}</div>
        </div>

        <div class="text-right sm:text-left">
            <div class="text-sm tabular-nums">{{ formatDuration(today) }}</div>
            <div class="text-xs muted">сегодня</div>
        </div>

        <div class="col-span-2 min-w-0 sm:col-span-1">
            <div v-if="running" class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm">{{ running.description }}</div>
                    <div class="text-xs muted">с {{ formatTime(running.started_at) }}</div>
                </div>
                <span class="font-mono text-lg tabular-nums">{{ formatClock(elapsed) }}</span>
                <button type="button" class="btn btn-stop" :disabled="stop.processing" @click="stopTimer">
                    <span class="size-2 rounded-[2px] bg-current" />
                    Стоп
                </button>
            </div>

            <form v-else class="flex gap-2" @submit.prevent="startTimer">
                <input
                    v-model="start.description"
                    class="input"
                    :class="{ 'input-error': error }"
                    maxlength="255"
                    placeholder="Над чем работаете?"
                    :aria-label="`Задача для ${employee.name}`"
                />
                <button type="submit" class="btn btn-primary" :disabled="start.processing">
                    <svg class="size-3" viewBox="0 0 12 12" fill="currentColor"><path d="M3 1.5v9l7.5-4.5z" /></svg>
                    Старт
                </button>
            </form>

            <p v-if="error" class="field-error">{{ error }}</p>
        </div>
    </li>
</template>
