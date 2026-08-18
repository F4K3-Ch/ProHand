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
            const container = document.querySelector('.error-success-message');
            const sessionErrorMessage = document.getElementById('sessionErrorMessage');
            const sessionSuccessMessage = document.getElementById('sessionSuccessMessage');
            const storageErrorMessage = document.getElementById('storageErrorMessage');
            const storageSuccessMessage = document.getElementById('storageSuccessMessage');

            if (!container) {
                return;
            }

            const storageError = sessionStorage.getItem('error_message');
            const storageSuccess = sessionStorage.getItem('success_message');

            if (storageError) {
                storageErrorMessage.textContent = storageError;
                storageErrorMessage.style.display = 'block';
            }

            if (storageSuccess) {
                storageSuccessMessage.textContent = storageSuccess;
                storageSuccessMessage.style.display = 'block';
            }

            sessionStorage.removeItem('error_message');
            sessionStorage.removeItem('success_message');

            const hasSessionMessage =
                sessionErrorMessage || sessionSuccessMessage;

            const hasStorageMessage =
                storageError || storageSuccess;

            if (hasSessionMessage || hasStorageMessage) {
                container.style.display = 'block';

                setTimeout(function () {
                    container.style.display = 'none';
                }, 3000);
            }
        });
    </script>

    @stack('scripts')
</body>
</html>