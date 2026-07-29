<div class="sidebar">
    <div class="sidebar-top">
        <div class="profile-info">
            <img id="sidebar-profile-img" class="profile-img">
            <div class="profile-text">
                <p id="sidebar-user-role" class="role">Loading...</p>
                <p id="sidebar-user-name" class="name">Loading...</p>
                <p id="sidebar-user-email" class="email">Loading...</p>
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
            <li><a href="#"><i class="fa-solid fa-circle-user"></i> My Profile</a></li>
            <li><a href="#" class="notif-link"><i class="fa-solid fa-bell"></i> Notifications</a></li>
            <li>
                <button type="button" id="logoutBtn">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </button>
            </li>
        </ul>
    </div>
    <div class="sidebar-bottom"><footer class="sidebar-footer"><p>&copy; {{ date('Y') }} ProHand.</p></footer></div>
</div>