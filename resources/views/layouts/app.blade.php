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
        const path = window.location.pathname;
        if (path !== '/login' && path !== '/register') {
            const token = localStorage.getItem('token');
            const userRole = localStorage.getItem('user_role');

            if (!token || !userRole) {
                localStorage.clear();
                window.location.href = "{{ route('login') }}";
            }
        }
    </script>

    <input type="checkbox" id="sidebar-toggle-check" class="sidebar-checkbox">

    @yield('sidebar')

    @yield('content')
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const token = localStorage.getItem('token');
            const path = window.location.pathname;

            if (token && path !== '/login' && path !== '/register') {
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
                    if (user.role) {
                        localStorage.setItem('user_role', user.role);
                    }

                    const role = user.role.trim().toLowerCase();

                    const rolePrefixes = {
                        'admin': ['/admin'],
                        'client': ['/client'],
                        'tasker': ['/tasker']
                    };

                    let authorizedRolesForCurrentPage = [];
                    for (const [allowedRole, prefixes] of Object.entries(rolePrefixes)) {
                        if (prefixes.some(prefix => path.startsWith(prefix))) {
                            authorizedRolesForCurrentPage.push(allowedRole);
                        }
                    }

                    if (authorizedRolesForCurrentPage.length > 0 && !authorizedRolesForCurrentPage.includes(role)) {
                        const lastSafePage = sessionStorage.getItem('last_known_safe_url');

                        if (lastSafePage && lastSafePage !== path) {
                            window.location.href = lastSafePage;
                        } else {
                            if (role === 'admin') {
                                window.location.href = "{{ route('admin.dashboard') }}";
                            } else if (role === 'tasker') {
                                window.location.href = "{{ route('tasker.dashboard') }}";
                            } else {
                                window.location.href = "{{ route('client.dashboard') }}";
                            }
                        }
                        return;
                    }

                    sessionStorage.setItem('last_known_safe_url', path);

                    const fullName = user.middle_name 
                        ? `${user.first_name} ${user.middle_name} ${user.last_name}` 
                        : `${user.first_name} ${user.last_name}`;

                    const nameEl = document.getElementById('sidebar-user-name');
                    const emailEl = document.getElementById('sidebar-user-email');
                    const roleEl = document.getElementById('sidebar-user-role');
                    const imgEl = document.getElementById('sidebar-profile-img');
                    const imgSpinner = document.getElementById('sidebar-img-spinner');

                    if (nameEl) nameEl.textContent = fullName;
                    if (emailEl) emailEl.textContent = user.email;
                    if (roleEl) roleEl.textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
                    
                    if (imgEl) {
                        if (user.profile_picture) {
                            imgEl.src = "{{ asset('storage') }}/" + user.profile_picture;
                        } else {
                            imgEl.src = "{{ asset('storage/uploads/profile_pictures/default.jpg') }}";
                        }
                        imgEl.style.display = 'block';
                    }
                    if (imgSpinner) {
                        imgSpinner.style.display = 'none';
                    }
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
                        sessionStorage.clear();
                        window.location.href = "{{ route('login') }}";
                    });
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>