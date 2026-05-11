<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @vite('resources/css/app.css')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.0/dist/cdn.min.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap"
        rel="stylesheet">
        <title>InternHub</title>
</head>
@stack('scripts')
<style>
    * {
        font-family: 'Google Sans', sans-serif;
    }
</style>

<body class="bg-gray-50">

    <div class="fixed bottom-5 right-5 z-50 flex flex-col space-y-3 items-end">

        @if (session('success'))
            <x-toast-notification type="success" :message="session('success')" />
        @endif

        @if ($errors->any())
            <x-toast-notification type="error" :message="$errors->first()" />
        @endif

    </div>

    <div class="overflow-hidden bg-white shadow-sm rounded-lg">
        @yield('section')
    </div>

</body>

</html>
