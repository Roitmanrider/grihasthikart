<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>
        @yield('title','GrihasthiKart')
    </title>

    <link rel="icon"
          href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet"
          href="{{ asset('assets/css/style.css') }}">

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @stack('styles')

</head>

<body>

    @yield('content')

    <script src="{{ asset('assets/js/app.js') }}"></script>

    @stack('scripts')

</body>

</html>
