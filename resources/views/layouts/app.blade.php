<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>@yield('title', 'ProHand')</title>
    @stack('styles')
</head>
<body>
    <script>
        const token = localStorage.getItem('token');
        const userRole = localStorage.getItem('user_role');

        if (!token || !userRole) {
            localStorage.clear();
            window.location.href = "{{ route('login') }}";
        }
    </script>

    <input type="checkbox" id="sidebar-toggle-check" class="sidebar-checkbox">

    <div id="sidebar-wrapper">
        @php
            $user = auth()->user();
        @endphp

        @if($user && $user->role === 'admin')
            @include('layouts.sidebar.admin')
        @elseif($user && $user->role === 'tasker')
            @include('layouts.sidebar.tasker')
        @else
            @include('layouts.sidebar.client')
        @endif
    </div>

    @yield('content')
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const token = localStorage.getItem('token');

            if (token) {
                fetch('/api/user', {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error('Unauthorized');
                    return response.json();
                })
                .then(user => {
                    const fullName = user.middle_name 
                        ? `${user.first_name} ${user.middle_name} ${user.last_name}` 
                        : `${user.first_name} ${user.last_name}`;

                    const nameEl = document.getElementById('sidebar-user-name');
                    const emailEl = document.getElementById('sidebar-user-email');
                    const roleEl = document.getElementById('sidebar-user-role');
                    const imgEl = document.getElementById('sidebar-profile-img');

                    if (nameEl) nameEl.textContent = fullName;
                    if (emailEl) emailEl.textContent = user.email;
                    if (roleEl) roleEl.textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
                    if (imgEl && user.profile_picture) imgEl.src = '/' + user.profile_picture;
                })
                .catch(() => {
                    localStorage.clear();
                    window.location.href = "{{ route('login') }}";
                });
            }

            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function (e) {
                    e.preventDefault();

                    fetch('/api/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .finally(() => {
                        localStorage.clear();
                        window.location.href = "{{ route('login') }}";
                    });
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>