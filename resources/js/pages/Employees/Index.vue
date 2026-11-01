<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import EmployeeRow from '../../components/EmployeeRow.vue';
import { useNow } from '../../lib/useNow';

const props = defineProps({
    employees: { type: Array, required: true },
});

const page = usePage();
const now = useNow();
const working = computed(() => props.employees.filter((e) => e.running).length);

const form = useForm({ name: '', position: '' });

function addEmployee() {
    form.post('/employees', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Сотрудники" />

    <div class="flex items-baseline justify-between gap-4">
        <h1 class="text-2xl font-semibold tracking-tight">Сотрудники</h1>
        <p v-if="employees.length" class="text-sm muted">
            работают {{ working }} из {{ employees.length }}
        </p>
    </div>

    <form class="mt-6 grid gap-2 sm:grid-cols-[1fr_1fr_auto]" @submit.prevent="addEmployee">
        <div>
            <input
                v-model="form.name"
                class="input"
                :class="{ 'input-error': form.errors.name }"
                maxlength="100"
                placeholder="Имя и фамилия"
                aria-label="Имя и фамилия"
            />
            <p v-if="form.errors.name" class="field-error">{{ form.errors.name }}</p>
        </div>
        <div>
            <input
                v-model="form.position"
                class="input"
                :class="{ 'input-error': form.errors.position }"
                maxlength="100"
                placeholder="Должность (необязательно)"
                aria-label="Должность"
            />
            <p v-if="form.errors.position" class="field-error">{{ form.errors.position }}</p>
        </div>
        <button type="submit" class="btn btn-ghost" :disabled="form.processing">Добавить</button>
    </form>

    <ul v-if="employees.length" class="mt-8 divide-y hairline border-y">
        <EmployeeRow
            v-for="employee in employees"
            :key="employee.id"
            :employee="employee"
            :now="now"
            :server-now="page.props.serverNow"
        />
    </ul>

    <div v-else class="mt-8 rounded-lg border border-dashed border-zinc-200 py-16 text-center dark:border-zinc-800">
        <p class="font-medium">Пока никого нет</p>
        <p class="mt-1 text-sm muted">Добавьте первого сотрудника, чтобы запустить таймер.</p>
    </div>
</template>
