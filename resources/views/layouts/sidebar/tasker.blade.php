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
                <a href="{{ route('tasker.dashboard') }}" class="{{ request()->routeIs('tasker.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('tasker.your_task') }}" class="{{ request()->routeIs('tasker.your_task') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-check"></i> Your Task
                </a>
            </li>
            <li><a href="#"><i class="fa-solid fa-clipboard-check"></i> Finished Errands</a></li>
            <li>
                <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-user"></i> My Profile
                </a>
            </li>
            <li>
                <a href="{{ route('notifications') }}" class="notif-link {{ request()->routeIs('notifications') ? 'active' : '' }}">
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