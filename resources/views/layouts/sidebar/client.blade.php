<div class="sidebar">
    <div class="sidebar-top">
        <div class="profile-info">
            <img src="{{ asset(auth()->user()->profile_picture) }}" class="profile-img">
            <div class="profile-text">
                <p class="role">{{ ucfirst(auth()->user()->role) }}</p>
                <p class="name">{{ auth()->user()->first_name }} {{ auth()->user()->middle_name }}  {{ auth()->user()->last_name }}</p>
                <p class="email">{{ auth()->user()->email }}</p>
            </div>
        </div>

        <ul class="sidebar-links">
            <li>
                <a href="{{ route('client.dashboard') }}"
                class="{{ request()->routeIs('client.dashboard', 'client.create.errand') || request()->query('from') === 'client.dashboard' ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('client.history') }}"
                class="{{ request()->routeIs('client.history') || request()->query('from') === 'client.history' ? 'active' : '' }}">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    History
                </a>
            </li>
            <li>
                <a href="{{ route('profile') }}"
                class="{{ request()->routeIs('profile', 'profile.edit', 'profile.change-password') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-user"></i>
                    My Profile
                </a>
            </li>
            <li>
                <a href="{{ route('notifications') }}"
                class="notif-link {{ request()->routeIs('notifications') || request()->query('from') === 'notifications' ? 'active' : '' }}">
                    <i class="fa-solid fa-bell"></i>
                    Notifications
                    <span id="notificationBadge" class="notif-badge" style="display: none;"></span>
                </a>
            </li>
            <li>
                <button type="button" id="logoutButton" class="logout-button">
                    <i class="fa-solid fa-right-from-bracket"></i> 
                    Logout
                    <div class="circle-spinner" id="logoutSpinner" style="display:none;"></div>
                </button>
            </li>
        </ul>
    </div>
    <div class="sidebar-bottom"><footer class="sidebar-footer"><p>&copy; {{ date('Y') }} ProHand.</p></footer></div>
</div>