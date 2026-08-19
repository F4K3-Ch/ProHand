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

    <div id="deleteModal" class="custom-modal-overlay" style="display: none;">
        <div class="custom-modal-card">
            <h3>Confirm Deletion</h3>
            <p id="deleteModalMessage"></p>
            <div class="custom-modal-actions">
                <button type="button" id="cancelDeleteBtn" class="btn-secondary">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn-delete">Delete</button>
            </div>
        </div>
    </div>

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
        // Session messages
        const sessionSuccessMessage = document.getElementById('sessionSuccessMessage');
        const sessionErrorMessage = document.getElementById('sessionErrorMessage');

        if (sessionSuccessMessage || sessionErrorMessage) {
            setTimeout(() => {
                if (sessionSuccessMessage) {
                    sessionSuccessMessage.textContent = '';
                    sessionSuccessMessage.style.display = 'none';
                }

                if (sessionErrorMessage) {
                    sessionErrorMessage.textContent = '';
                    sessionErrorMessage.style.display = 'none';
                }
            }, 3000);
        }

        // Success message from sessionStorage
        const successMessage = sessionStorage.getItem('success_message');
        const successMessageText = document.getElementById('successMessageText');

        if (successMessage && successMessageText) {
            successMessageText.textContent = successMessage;
            successMessageText.style.display = 'block';

            sessionStorage.removeItem('success_message');

            setTimeout(() => {
                successMessageText.textContent = '';
                successMessageText.style.display = 'none';
            }, 3000);
        }

        // Error message from sessionStorage
        const errorMessage = sessionStorage.getItem('error_message');
        const errorMessageText = document.getElementById('errorMessageText');

        if (errorMessage && errorMessageText) {
            errorMessageText.textContent = errorMessage;
            errorMessageText.style.display = 'block';

            sessionStorage.removeItem('error_message');

            setTimeout(() => {
                errorMessageText.textContent = '';
                errorMessageText.style.display = 'none';
            }, 3000);
        }

        function showDeleteConfirmation(messageText) {
            return new Promise((resolve) => {
                const modal = document.getElementById('deleteModal');
                document.getElementById('deleteModalMessage').textContent = messageText;
                modal.style.display = 'flex';

                const close = (result) => {
                    modal.style.display = 'none';
                    resolve(result);
                };

                document.getElementById('confirmDeleteBtn').onclick = () => close(true);
                document.getElementById('cancelDeleteBtn').onclick = () => close(false);
            });
        }
    </script>

    @stack('scripts')
</body>
</html>