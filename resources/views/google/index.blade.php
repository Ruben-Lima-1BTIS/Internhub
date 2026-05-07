<!DOCTYPE html>
<html>
<head>
    <title>Gmail API Quickstart</title>
    <meta charset="utf-8" />
</head>
<body>
    <p>Gmail API Quickstart</p>

    {{-- Equivalent to: authorize_button / signout_button --}}
    @if (!$isAuthenticated)
        <a href="{{ route('google.connect') }}">
            <button>Authorize</button>
        </a>
    @else
        <a href="{{ route('google.labels') }}">
            <button>Refresh</button>
        </a>
        <a href="{{ route('google.signout') }}">
            <button>Sign Out</button>
        </a>
    @endif

    {{-- Equivalent to: <pre id="content"> --}}
    <pre style="white-space: pre-wrap;">
        @if (session('error'))
            {{ session('error') }}
        @endif

        @isset($labels)
            @if (count($labels) === 0)
                No labels found.
            @else
                Labels:
                @foreach ($labels as $label)
                    {{ $label->getName() }}
                @endforeach
            @endif
        @endisset
    </pre>
</body>
</html>