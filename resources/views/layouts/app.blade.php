<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>@yield('title', 'ProHand')</title>
</head>
<body class="dashboard-body">

    {{-- The header is now a section that pages can override --}}
    @section('header')
        <header class="default-header">
            <img src="{{ asset('img/ProHand(Adapt).png') }}" alt="Logo">
        </header>
    @show

    <div class="app-container">
        @include('layouts.sidebar.' . auth()->user()->role)

        <main class="main-content">
            @yield('content')
        </main>
    </div>

</body>
</html>