@extends('layouts.app')

@section('title', 'Client Dashboard')

@section('sidebar')
    @include('layouts.sidebar.client')
@endsection

@section('content')
    <main class="main">
        <div class="fixed-wrapper">
            <header class="header"> 
                <div class="header-left">
                    <div class="logo">
                        <img src="{{ asset('img/ProHand(Adapt).png') }}" alt="ProHand Logo" class="logo-img">
                        <div class="title">
                            <h1>Pro<span>Hand</span></h1>
                            <h2>Dashboard</h2>
                        </div>

                        <label for="sidebar-toggle-check" class="sidebar-toggle-btn" aria-label="Toggle Navigation Menu">
                            <i class="fa-solid fa-bars"></i>
                        </label>
                    </div>
                </div>
                <div class="header-right">
                    <form class="search-form" method="GET" action="{{ route('client.dashboard') }}">
                        <div class="search-wrapper">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search">
                            @if (request()->has('search'))
                                <a href="{{ route('client.dashboard') }}" class="clear">Clear</a>
                            @endif
                        </div>
                        <button type="submit" class="btn-search">Search</button>
                    </form>
                </div>
            </header>
        </div>
        
        <div class="dashboard">
            <div class="error-success-message" id="flashMessageContainer" style="display: none;">
                <p class="success" id="flashMessageText"></p>
            </div>

            <h1>Client Dashboard</h1>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const successMsg = sessionStorage.getItem('success_message');
            if (successMsg) {
                const container = document.getElementById('flashMessageContainer');
                const textEl = document.getElementById('flashMessageText');
                
                textEl.textContent = successMsg;
                container.style.display = 'block';

                sessionStorage.removeItem('success_message');
            }
        });
    </script>
@endpush