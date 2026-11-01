<script setup>
import { onMounted, ref } from 'vue';

// The class itself is set before the first paint in app.blade.php; this only flips it.
const dark = ref(false);

onMounted(() => (dark.value = document.documentElement.classList.contains('dark')));

function toggle() {
    dark.value = !dark.value;
    document.documentElement.classList.toggle('dark', dark.value);
    try {
        localStorage.setItem('theme', dark.value ? 'dark' : 'light');
    } catch {
        // Private mode: the choice lasts until the page is reloaded.
    }
}
</script>

<template>
    <button
        type="button"
        class="grid size-9 cursor-pointer place-items-center rounded-md text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-900 dark:hover:text-zinc-100"
        :aria-label="dark ? 'Светлая тема' : 'Тёмная тема'"
        :title="dark ? 'Светлая тема' : 'Тёмная тема'"
        @click="toggle"
    >
        <!-- sun -->
        <svg v-if="dark" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
        </svg>
        <!-- moon -->
        <svg v-else class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5Z" />
        </svg>
    </button>
</template>
