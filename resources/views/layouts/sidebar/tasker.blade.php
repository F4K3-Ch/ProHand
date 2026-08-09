<div class="sidebar">
    <div class="sidebar-top">
        <div class="profile-info">
            <img id="sidebar-profile-img" class="profile-img" alt="Profile Picture" style="display: none;">
            <div id="sidebar-img-spinner" class="circle-spinner" style="width: 40px; height: 40px; border-width: 2px;"></div>
            
            <div class="profile-text">
                <p id="sidebar-user-role" class="role">Loading...</p>
                <p id="sidebar-user-name" class="name">Loading...</p>
                <p id="sidebar-user-email" class="email">Loading...</p>
            </div>
        </div>

        <ul class="sidebar-links">
            <li>
                <a href="{{ route('tasker.dashboard') }}" class="{{ request()->routeIs('tasker.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i> Dashboard
                </a>
            </li>
            <li><a href="#"><i class="fa-solid fa-clipboard-list"></i> Your Task</a></li>
            <li><a href="#"><i class="fa-solid fa-clipboard-check"></i> Finished Errands</a></li>
            <li>
                <a href="{{ route('tasker.profile') }}" class="{{ request()->routeIs('*.profile') ? 'active' : '' }}">
                    <i class="fa-solid fa-circle-user"></i> My Profile
                </a>
            </li>
            <li><a href="#" class="notif-link"><i class="fa-solid fa-bell"></i> Notifications</a></li>
            <li>
                <button type="button" id="logoutBtn">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                    <span
                        id="logoutSpinner"
                        class="circle-spinner"
                        style="display:none;">
                    </span>
                </button>
            </li>
        </ul>
    </div>
    <div class="sidebar-bottom"><footer class="sidebar-footer"><p>&copy; {{ date('Y') }} ProHand.</p></footer></div>
</div>