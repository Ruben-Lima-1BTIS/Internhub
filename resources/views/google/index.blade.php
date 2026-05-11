<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gmail API Quickstart</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-lg p-8 text-center">
        <h1 class="text-2xl font-semibold mb-6">
            Gmail API Quickstart
        </h1>

        <div class="flex flex-wrap gap-3 mb-6 justify-center">
            @if (!$isAuthenticated)
                <a href="{{ route('google.connect') }}">
                    <button
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition">
                        Authorize
                    </button>
                </a>
            @else
                <a href="{{ route('google.labels') }}">
                    <button
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition">
                        Refresh
                    </button>
                </a>

                <a href="{{ route('google.signout') }}">
                    <button
                        class="px-4 py-2 rounded-lg bg-gray-200 text-gray-800 font-medium hover:bg-gray-300 transition">
                        Sign Out
                    </button>
                </a>
            @endif
        </div>

        <div class="bg-slate-50 rounded-xl p-4 text-sm whitespace-pre-wrap">
            @if (session('error'))
                <div class="text-red-600 font-semibold mb-3">
                    {{ session('error') }}
                </div>
            @endif

            @isset($labels)
                @if (count($labels) === 0)
                    <div>No labels found.</div>
                @else
                    <div class="font-semibold mb-2">Labels:</div>

                    <ul class="space-y-1">
                        @foreach ($labels as $label)
                            <li>{{ $label->getName() }}</li>
                        @endforeach
                    </ul>
                @endif
            @endisset
        </div>
    </div>

</body>
</html>