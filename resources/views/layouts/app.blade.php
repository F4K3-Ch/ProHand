<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>@yield('title', 'ProHand')</title>
    @stack('styles')
</head>
<body>

    <input type="checkbox" id="sidebar-toggle-check" class="sidebar-checkbox">

    @yield('sidebar')

    @yield('content')

    <script>
        const logoutButton = document.getElementById('logoutButton');

        if (logoutButton) {
            logoutButton.addEventListener('click', async function () {
                this.disabled = true;

                try {
                    const response = await fetch("{{ url('/logout') }}", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'same-origin'
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.message || 'Logout failed.');
                    }

                    window.location.href = "{{ route('login') }}";

                } catch (error) {
                    console.error(error);
                    this.disabled = false;
                }
            });
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('flashMessageContainer');
            const text = document.getElementById('flashMessageText');

            if (!container || !text) {
                return;
            }

            const sessionError = container.dataset.error;
            const sessionSuccess = container.dataset.success;

            const storageError = sessionStorage.getItem('error_message');
            const storageSuccess = sessionStorage.getItem('success_message');

            const errorMessage = sessionError || storageError;
            const successMessage = sessionSuccess || storageSuccess;

            if (successMessage) {
                text.textContent = successMessage;
                text.className = 'success';
                container.style.display = 'block';
            } else if (errorMessage) {
                text.textContent = errorMessage;
                text.className = 'error';
                container.style.display = 'block';
            }

            sessionStorage.removeItem('success_message');
            sessionStorage.removeItem('error_message');

            if (successMessage || errorMessage) {
                setTimeout(function () {
                    container.style.display = 'none';
                }, 3000);
            }
        });
    </script>

    @stack('scripts')
</body>
</html>