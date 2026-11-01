<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>{{ config('app.name') }}</title>
    <script>
        // Before the first paint: the saved theme (or the system one), and the
        // browser's time zone for the server's "today" and report periods.
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var dark = saved ? saved === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {}
            try {
                var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
                if (tz) document.cookie = 'tz=' + encodeURIComponent(tz) + ';path=/;max-age=31536000;samesite=lax';
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
