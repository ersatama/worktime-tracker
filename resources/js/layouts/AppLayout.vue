<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ThemeToggle from '../components/ThemeToggle.vue';

const page = usePage();

const nav = [
    { href: '/', label: 'Сотрудники', active: (url) => !url.startsWith('/reports') },
    { href: '/reports', label: 'Отчёт', active: (url) => url.startsWith('/reports') },
];

// The success message fades out by itself; every new message shows again.
const flash = ref(null);
let hideTimer;
watch(
    () => page.props.flash?.success,
    (message) => {
        if (!message) return;
        flash.value = message;
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => (flash.value = null), 3000);
    },
    { immediate: true },
);

const url = computed(() => page.url);
</script>

<template>
    <div class="mx-auto flex min-h-dvh max-w-4xl flex-col px-4 sm:px-6">
        <header class="flex h-16 items-center gap-6 border-b hairline">
            <Link href="/" class="flex items-center gap-2 text-sm font-semibold tracking-tight">
                <span class="size-2 rounded-full bg-zinc-900 dark:bg-zinc-100" />
                Учёт времени
            </Link>
            <nav class="flex gap-1 text-sm">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="rounded-md px-3 py-1.5 transition"
                    :class="item.active(url)
                        ? 'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100'
                        : 'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100'"
                >
                    {{ item.label }}
                </Link>
            </nav>
            <div class="ml-auto">
                <ThemeToggle />
            </div>
        </header>

        <main class="flex-1 py-8 sm:py-10">
            <slot />
        </main>

        <footer class="border-t hairline py-6 text-xs muted">
            Данные вымышленные · время в часовом поясе браузера
        </footer>

        <Transition
            enter-from-class="translate-y-2 opacity-0"
            leave-to-class="translate-y-2 opacity-0"
            enter-active-class="transition duration-200"
            leave-active-class="transition duration-200"
        >
            <div
                v-if="flash"
                role="status"
                class="fixed bottom-6 left-1/2 max-w-[calc(100vw-2rem)] -translate-x-1/2 rounded-md bg-zinc-900 px-4 py-2.5 text-sm text-white shadow-lg dark:bg-zinc-100 dark:text-zinc-900"
            >
                {{ flash }}
            </div>
        </Transition>
    </div>
</template>
