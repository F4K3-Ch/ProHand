<div class="sidebar">
    <div class="sidebar-top">
        <div class="profile-info">
            <img src="#" class="profile-img">
            <div class="profile-text">
                <p class="role">Client</p>
                <p class="name">#</p>
                <p class="email">#</p>
            </div>
        </div>

        <ul class="sidebar-links">
            <li>
                <a href="{{ route('client.dashboard') }}" class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-house"></i> Dashboard
                </a>
            </li>
            <li><a href="#"><i class="fa-solid fa-clipboard-check"></i> Finished Errands</a></li>
            <li><a href="#"><i class="fa-solid fa-circle-plus"></i> Create Errand</a></li>
            <li><a href="#"><i class="fa-solid fa-circle-user"></i> My Profile</a></li>
            <li><a href="#" class="notif-link"><i class="fa-solid fa-bell"></i> Notifications</a></li>
            <li>
                <button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
            </li>
        </ul>
    </div>
    <div class="sidebar-bottom"><footer class="sidebar-footer"><p>&copy; {{ date('Y') }} ProHand.</p></footer></div>
</div>