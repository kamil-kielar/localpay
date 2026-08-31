<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LokalPay Pro')</title>
    <meta name="description" content="@yield('description', 'LokalPay Pro — bezpieczne zarządzanie najmem i płatnościami.')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-page="@yield('page', 'static')">
    @yield('content')
</body>
</html>
