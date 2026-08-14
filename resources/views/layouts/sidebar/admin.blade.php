<div class="sidebar">
    <div class="sidebar-top">
        <div class="profile-info">
            <img src="{{ asset(auth()->user()->profile_picture) }}" class="profile-img">
            <div class="profile-text">
                <p class="role">{{ ucfirst(auth()->user()->role) }}</p>
                <p class="name">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                <p class="email">{{ auth()->user()->email }}</p>
            </div>
        </div>

        <ul class="sidebar-links">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i> Dashboard
                </a>
            </li>
            <li><a href="#"><i class="fa-solid fa-clipboard-check"></i> Finished Errands</a></li>
            <li><a href="#"><i class="fa-solid fa-users"></i> Manage Users</a></li>
            <li>
                <a href="#" class="{{ request()->routeIs('profile') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-user"></i> My Profile
                </a>
            </li>
            <li><a href="#" class="notif-link"><i class="fa-solid fa-bell"></i> Notifications</a></li>
            <li>
                <button type="button" id="logoutButton" class="logout-button">
                    <i class="fa-solid fa-right-from-bracket"></i> 
                    Logout
                </button>
            </li>
        </ul>
    </div>
    <div class="sidebar-bottom"><footer class="sidebar-footer"><p>&copy; {{ date('Y') }} ProHand.</p></footer></div>
</div>