const pad = (n) => String(n).padStart(2, '0');

/** 3725 → "1:02:05" — a running timer. */
export function formatClock(seconds) {
    const s = Math.max(0, Math.floor(seconds));
    return `${Math.floor(s / 3600)}:${pad(Math.floor((s % 3600) / 60))}:${pad(s % 60)}`;
}

/** 3725 → "1 ч 02 мин", 45 → "45 с", 0 → "0 мин" — totals. */
export function formatDuration(seconds) {
    const s = Math.max(0, Math.floor(seconds));
    if (s === 0) return '0 мин';
    if (s < 60) return `${s} с`;
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    return h ? `${h} ч ${pad(m)} мин` : `${m} мин`;
}

const dateTime = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
const time = new Intl.DateTimeFormat('ru-RU', { hour: '2-digit', minute: '2-digit' });

export const formatDateTime = (iso) => dateTime.format(new Date(iso));
export const formatTime = (iso) => time.format(new Date(iso));

/** A Date → "2026-11-02" in the viewer's own calendar (not UTC). */
export function isoDay(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}
