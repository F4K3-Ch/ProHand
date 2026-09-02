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

                    <button
                        type="button"
                        id="deleteAllButton"
                        class="btn-delete-all"
                        style="display:none;"
                        onclick="return confirm('Are you sure you want to delete all notifications?')">
                        Delete All Notifications
                    </button>
                </div>

                <ul class="notifications-list" id="notificationsList">
                    <li class="notification-card">
                        <div class="notif-message">
                            <span class="circle-spinner" style="width:20px;height:20px;border-width:2px;"></span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </main>

    <template id="notification-card-template">
        <li class="notification-card">
            <div class="notif-message">
                <h3 class="notification-type"></h3>
                <p class="notification-message"></p>
                <small class="notification-date"></small>
            </div>

            <div class="notification-buttons">
                <a href="#" class="btn-view view-link">View</a>
                <button type="button" class="btn-delete">Delete</button>
            </div>
        </li>
    </template>

    <template id="notification-empty-template">
        <li class="notification-card">
            <div class="notif-message">
                <h3>No Notifications</h3>
                <p>You don't have any notifications yet.</p>
            </div>
        </li>
    </template>

    <template id="notification-loading-template">
        <li class="notification-card">
            <div class="notif-message">
                <span class="circle-spinner" style="width:20px;height:20px;border-width:2px;"></span>
            </div>
        </li>
    </template>

    <template id="notification-error-template">
        <li class="notification-card">
            <div class="notif-message">
                <h3>Unable to Load Notifications</h3>
                <p class="notification-error-text"></p>
            </div>
        </li>
    </template>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const notificationsList = document.getElementById('notificationsList');
            const searchInput = document.getElementById('notificationSearch');
            const searchButton = document.getElementById('searchButton');
            const clearSearchButton = document.getElementById('clearSearchButton');
            const errorText = document.getElementById('errorMessageText');

            let currentSearch = '';

            loadNotifications();
            markNotificationsAsRead();
            updateClearButton();

            async function loadNotifications(){
                showLoading();

                const params = new URLSearchParams();

                if(currentSearch !== ''){
                    params.append('search', currentSearch);
                }

                try{
                    const response = await fetch('/api/notifications?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-API-KEY': apiKey,
                            'X-MY-ORIGIN': origin
                        },
                        credentials: 'same-origin'
                    });

                    const result = await response.json();

                    if(!response.ok || !result.success){
                        throw new Error(
                            result.message || 'Failed to load notifications.'
                        );
                    }

                    displayNotifications(result.notifications);

                }catch(error){
                    console.error('Notifications error:', error);

                    displayGlobalError(error.message);
                    showError(error.message || 'Unable to load notifications.');
                }
            }

            async function markNotificationsAsRead() {
                try {
                    await fetch('/api/notifications/mark-as-read', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-API-KEY': apiKey,
                            'X-MY-ORIGIN': origin
                        },
                        credentials: 'same-origin'
                    });

                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        badge.style.display = 'none';
                    }
                } catch (error) {
                    console.error('Failed to mark notifications as read:', error);
                }
            }

            function displayNotifications(notifications){
                notificationsList.innerHTML = '';

                const deleteAllButton = document.getElementById('deleteAllButton');

                if(!notifications || notifications.length === 0){
                    if(deleteAllButton){
                        deleteAllButton.style.display = 'none';
                    }

                    notificationsList.appendChild(
                        document.getElementById('notification-empty-template')
                            .content.cloneNode(true)
                    );

                    return;
                }

                if(deleteAllButton){
                    deleteAllButton.style.display = 'inline-block';
                }

                const template = document.getElementById('notification-card-template');

                notifications.forEach(function(notification){
                    const card = template.content.cloneNode(true);
                    const typeElement = card.querySelector('.notification-type');
                    const messageElement = card.querySelector('.notification-message');
                    const dateElement = card.querySelector('.notification-date');
                    const viewLink = card.querySelector('.view-link');

                    typeElement.textContent = notification.type;

                    messageElement.textContent = notification.message;

                    dateElement.textContent = notification.created_at;

                    if(notification.link){
                        viewLink.href = notification.link;
                    }else{
                        viewLink.style.display = 'none';
                    }

                    notificationsList.appendChild(card);
                });
            }

            function showLoading(){
                notificationsList.innerHTML = '';

                notificationsList.appendChild(
                    document.getElementById('notification-loading-template')
                        .content.cloneNode(true)
                );
            }

            function showError(message){
                notificationsList.innerHTML = '';
                const template = document.getElementById('notification-error-template');
                const card = template.content.cloneNode(true);
                const errorMessage = card.querySelector('.notification-error-text');

                if(errorMessage){
                    errorMessage.textContent = message;
                }

                notificationsList.appendChild(card);
            }

            function displayGlobalError(message){
                if(!errorText) return;

                errorText.textContent = message;
                errorText.style.display = 'block';

                setTimeout(() => {
                    errorText.textContent = '';
                    errorText.style.display = 'none';
                }, 5000);
            }

            function updateClearButton(){
                clearSearchButton.style.display =
                    searchInput.value.trim() !== ''
                        ? 'inline-block'
                        : 'none';
            }

            searchButton.addEventListener('click', function(event){
                event.preventDefault();

                currentSearch = searchInput.value.trim();

                loadNotifications();
            });

            searchInput.addEventListener('keydown', function(event){
                if(event.key === 'Enter'){
                    event.preventDefault();

                    currentSearch = searchInput.value.trim();

                    loadNotifications();
                }
            });

            searchInput.addEventListener('input', updateClearButton);

            clearSearchButton.addEventListener('click', function(event){
                event.preventDefault();

                searchInput.value = '';
                currentSearch = '';

                updateClearButton();
                loadNotifications();
            });
        });
    </script>
@endpush