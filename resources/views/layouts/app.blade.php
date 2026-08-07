<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>@yield('title', 'ProHand')</title>
    @stack('styles')
</head>
<body>
    <script>
        const currentPath = window.location.pathname;
        const token = localStorage.getItem('token');

        if ( currentPath !== '/login' && currentPath !== '/register' && !token) {
            window.location.href = "{{ route('login') }}";
        }
    </script>

    <input type="checkbox" id="sidebar-toggle-check" class="sidebar-checkbox">

    @yield('sidebar')

    @yield('content')

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const path = window.location.pathname;
            const token = localStorage.getItem('token');

            if (path === '/login' || path === '/register') {
                return;
            }

            if (!token) {
                window.location.href = "{{ route('login') }}";
                return;
            }

            try {
                const response = await fetch('/api/me', {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Unauthorized');
                }

                const data = await response.json();
                const user = data.user;

                localStorage.setItem('user', JSON.stringify(user));

                const role = user.role.toLowerCase();

                if (path.startsWith('/admin') && role !== 'admin') {
                    window.location.href = '/login';
                    return;
                }

                if (path.startsWith('/tasker') && role !== 'tasker') {
                    window.location.href = '/login';
                    return;
                }

                if (path.startsWith('/client') && role !== 'client') {
                    window.location.href = '/login';
                    return;
                }

                const fullName = user.middle_name
                    ? `${user.first_name} ${user.middle_name} ${user.last_name}`
                    : `${user.first_name} ${user.last_name}`;

                const nameElement = document.getElementById('sidebar-user-name');
                const emailElement = document.getElementById('sidebar-user-email');
                const roleElement = document.getElementById('sidebar-user-role');
                const imageElement = document.getElementById('sidebar-profile-img');
                const spinnerElement = document.getElementById('sidebar-img-spinner');

                if (nameElement) {
                    nameElement.textContent = fullName;
                }

                if (emailElement) {
                    emailElement.textContent = user.email;
                }

                if (roleElement) {
                    roleElement.textContent =
                        user.role.charAt(0).toUpperCase() +
                        user.role.slice(1);
                }

                if (imageElement) {
                    imageElement.src = user.profile_picture
                        ? "{{ asset('storage') }}/" + user.profile_picture
                        : "{{ asset('storage/uploads/profile_pictures/default.jpg') }}";
                    imageElement.style.display = 'block';
                }

                if (spinnerElement) {
                    spinnerElement.style.display = 'none';
                }
            }

            catch (error) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                sessionStorage.clear();
                window.location.href = "{{ route('login') }}";
            }

            const logoutButton = document.getElementById('logoutBtn');
            const logoutSpinner = document.getElementById('logoutSpinner');

            if (logoutButton) {
                logoutButton.addEventListener('click', async function (e) {
                    e.preventDefault();
                    logoutButton.disabled = true;
                    if (logoutSpinner) {
                        logoutSpinner.style.display = 'inline-block';
                    }
                    try {
                        await fetch('/api/logout', {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${token}`,
                                'Accept': 'application/json'
                            }
                        });
                    }
                    catch (error) {
                        console.error(error);
                    }
                    finally {
                        localStorage.removeItem('token');
                        localStorage.removeItem('user');
                        sessionStorage.clear();
                        window.location.href = "{{ route('login') }}";
                    }
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>