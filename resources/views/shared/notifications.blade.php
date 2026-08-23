@extends('layouts.app')

@section('title', 'Notifications - ProHand')

@section('sidebar')
    @include('layouts.sidebar.' . auth()->user()->role)
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
                            <h2>Notifications</h2>
                        </div>

                        <label for="sidebar-toggle-check" class="sidebar-toggle-btn" aria-label="Toggle Navigation Menu">
                            <i class="fa-solid fa-bars"></i>
                        </label>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="search-wrapper">
                        <div class="search-bar">
                            <input type="text" id="notificationSearch" placeholder="Search notifications...">
                            <button type="button" class="btn-clear" id="clearSearchButton">Clear</button>
                        </div>
                        <button type="button" class="btn-search" id="searchButton">Search</button>
                    </div>
                </div>
            </header>
        </div>

        <div class="notifications">
            <div class="error-success-message">
                @if(session('success'))
                    <p class="success" id="sessionSuccessMessage">{{ session('success') }}</p>
                @endif

                @if(session('error'))
                    <p class="error" id="sessionErrorMessage">{{ session('error') }}</p>
                @endif

                <p class="success" id="successMessageText" style="display: none;"></p>
                <p class="error" id="errorMessageText" style="display: none;"></p>
            </div>

            <div class="notifications-contents">
                <div class="notif-contents-header">
                    <h3>Notifications</h3>
                    <button type="button"  class="btn-delete-all" onclick="return confirm('Are you sure you want to delete all notifications?')">
                        Delete All Notifications
                    </button>
                </div>

                <ul class="notifications-list">
                    <li class="notification-card">
                        <div class="notif-message">
                            <!-- Display this part the notification data -->
                            <h3>Notif Type</h3> 
                            <p>message</p>
                            <small>date</small>
                        </div>

                        <div class="notification-buttons">
                            <a href="#" class="btn-view view-link">View</a>
                            <button type="button" class="btn-delete">Delete</button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </main>
@endsection

@push('scripts')

@endpush