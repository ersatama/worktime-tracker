import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * The server's "now" in milliseconds, ticking every second. The offset to the
 * viewer's clock is re-taken from the serverNow prop of every response, so a
 * wrong clock on the viewer's computer does not make running timers jump.
 */
export function useNow() {
    const page = usePage();
    const local = ref(Date.now());
    const offset = ref(0);
    let timer;

    watch(
        () => page.props.serverNow,
        (iso) => (offset.value = iso ? Date.parse(iso) - Date.now() : 0),
        { immediate: true },
    );
    onMounted(() => (timer = setInterval(() => (local.value = Date.now()), 1000)));
    onBeforeUnmount(() => clearInterval(timer));

    return computed(() => local.value + offset.value);
}
