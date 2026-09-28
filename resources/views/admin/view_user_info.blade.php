@extends('layouts.app')

@section('title', 'User Profile - ProHand')

@section('sidebar')
    @include('layouts.sidebar.admin')
@endsection

@section('content')
    <a href="{{ route(request('from', 'admin.manage-users')) }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <div class="print-button-container">
        <button type="button" id="printUserBtn" class="btn-print">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>

    <main class="view-main" data-user-id="{{ $id }}">
        <div class="profile-info-container">
            <div class="profile-container">
                <h1><i class="fa-solid fa-user"></i> User Profile</h1>

                <div class="view-main-error-success-message">
                    @if(session('success'))
                        <p class="success" id="sessionSuccessMessage">{{ session('success') }}</p>
                    @endif
                    @if(session('error'))
                        <p class="error" id="sessionErrorMessage">{{ session('error') }}</p>
                    @endif
                    <p class="success" id="successMessageText" style="display:none;"></p>
                    <p class="error" id="errorMessageText" style="display:none;"></p>
                </div>

                <div class="profile-card-content">
                    <div class="profile-card-layout">
                        <div class="profile-image-area">
                            <div class="profile-image-wrapper">
                                <img id="profile-preview-photo"
                                    class="profile-image"
                                    src=""
                                    alt="Profile Picture"
                                    style="display:none;">

                                <div id="profile-photo-spinner"
                                    class="circle-spinner"
                                    style="width:35px;height:35px;border-width:3px;"></div>
                            </div>
                        </div>

                        <div class="profile-info-list">
                            <div class="profile-info-row">
                                <span><i class="fa-solid fa-id-card"></i> User ID</span>
                                <span id="profile-id" class="profile-value">Loading...</span>
                            </div>

                            <div class="profile-info-row">
                                <span><i class="fa-solid fa-envelope"></i> Email</span>
                                <span id="profile-email" class="profile-value">Loading...</span>
                            </div>

                            <div class="profile-info-row">
                                <span><i class="fa-solid fa-user"></i> Name</span>
                                <span id="profile-name" class="profile-value">Loading...</span>
                            </div>

                            <div class="profile-info-row">
                                <span><i class="fa-solid fa-user-tag"></i> Role</span>
                                <span id="profile-role" class="profile-value role-field">Loading...</span>
                            </div>

                            <div class="profile-info-row">
                                <span><i class="fa-solid fa-phone"></i> Contact Number</span>
                                <span id="profile-contact-number" class="profile-value">Loading...</span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-extra-info">
                        <div class="profile-info-row">
                            <span><i class="fa-solid fa-signature"></i> Last Name</span>
                            <span id="profile-last-name" class="profile-value">Loading...</span>
                        </div>

                        <div class="profile-info-row">
                            <span><i class="fa-solid fa-signature"></i> First Name</span>
                            <span id="profile-first-name" class="profile-value">Loading...</span>
                        </div>

                        <div class="profile-info-row">
                            <span><i class="fa-solid fa-signature"></i> Middle Name</span>
                            <span id="profile-middle-name" class="profile-value">Loading...</span>
                        </div>

                        <div class="profile-info-row">
                            <span><i class="fa-solid fa-calendar-plus"></i> Created At</span>
                            <span id="profile-created-at" class="profile-value">Loading...</span>
                        </div>
                    </div>

                    <div class="profile-bot-btn">
                        <button type="button" id="btn-delete-account" class="btn-delete-account">
                            <i class="fa-regular fa-trash-can"></i>
                            Delete Account
                        </button>

                        <p id="admin-restricted-msg" style="display:none;">
                            Admin accounts cannot be edited or deleted.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <section class="profile-metrics" id="profileMetrics" style="display:none;">
            <h3>Errand Metrics</h3>

            <div class="profile-total-cards">
                <div class="total-card bg-pending" id="profile-card-pending" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-pending"></span>
                        <h3>Pending</h3>
                    </div>
                    <p id="profile-metric-pending">
                        <span class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                    </p>
                </div>

                <div class="total-card bg-accepted" id="profile-card-accepted" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-accepted"></span>
                        <h3>Accepted</h3>
                    </div>
                    <p id="profile-metric-accepted">
                        <span class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                    </p>
                </div>

                <div class="total-card bg-in-progress" id="profile-card-in-progress" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-progress"></span>
                        <h3>In Progress</h3>
                    </div>
                    <p id="profile-metric-in-progress">
                        <span class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                    </p>
                </div>

                <div class="total-card bg-awaiting-confirmation" id="profile-card-awaiting-confirmation" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-awaiting-confirmation"></span>
                        <h3>Awaiting Confirmation</h3>
                    </div>
                    <p id="profile-metric-awaiting-confirmation">
                        <span class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                    </p>
                </div>

                <div class="total-card bg-dispute" id="profile-card-dispute" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-dispute"></span>
                        <h3>Dispute</h3>
                    </div>
                    <p id="profile-metric-dispute">
                        <span class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                    </p>
                </div>

                <div class="total-card bg-total span-full" id="profile-card-total-active" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-total"></span>
                        <h3>Total Active Errands</h3>
                    </div>
                    <p id="profile-metric-total-active">
                        <span class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                    </p>
                </div>

                <div class="total-card bg-completed" id="profile-card-completed" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-completed"></span>
                        <h3>Completed</h3>
                    </div>
                    <p id="profile-metric-completed"></p>
                </div>

                <div class="total-card bg-failed" id="profile-card-failed" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-failed"></span>
                        <h3>Failed</h3>
                    </div>
                    <p id="profile-metric-failed"></p>
                </div>

                <div class="total-card bg-total span-full" id="profile-card-total-history" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-total"></span>
                        <h3>Total History Errands</h3>
                    </div>
                    <p id="profile-metric-total-history">
                        <span class="circle-spinner" style="width:15px;height:15px;border-width:2px;"></span>
                    </p>
                </div>

                <div class="total-card bg-completed span-full" id="profile-card-income" style="display:none;">
                    <div class="card-header">
                        <span class="status-circle circle-completed"></span>
                        <h3>Income</h3>
                    </div>
                    <p id="profile-metric-income"></p>
                </div>
            </div>
        </section>
    </main>

    <div id="profilePhotoModal" class="profile-modal-overlay" style="display:none;">
        <button type="button" id="closePhotoModal" class="profile-modal-close" aria-label="Close modal">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="profile-modal-content-wrapper">
            <img id="modalProfileImage" class="profile-modal-img" alt="Enlarged Profile Picture">
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const mainContainer = document.querySelector('.view-main');
            const userId = mainContainer ? mainContainer.getAttribute('data-user-id') : null;

            const errorMessage = document.getElementById('errorMessageText');
            const successMessage = document.getElementById('successMessageText');

            const profilePhoto = document.getElementById('profile-preview-photo');
            const profilePhotoSpinner = document.getElementById('profile-photo-spinner');

            const photoModal = document.getElementById('profilePhotoModal');
            const modalImg = document.getElementById('modalProfileImage');
            const closePhotoBtn = document.getElementById('closePhotoModal');

            const printUserBtn = document.getElementById('printUserBtn');

            let user;

            if (printUserBtn) {
                printUserBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            profilePhoto.addEventListener('click', function () {
                if (profilePhoto.src) {
                    modalImg.src = profilePhoto.src;
                    photoModal.style.display = 'flex';

                    setTimeout(() => photoModal.classList.add('active'), 10);

                    document.body.style.overflow = 'hidden';
                }
            });

            function closeModalFunc() {
                photoModal.classList.remove('active');
                document.body.style.overflow = '';

                setTimeout(() => {
                    photoModal.style.display = 'none';
                }, 300);
            }

            closePhotoBtn.addEventListener('click', closeModalFunc);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && photoModal.style.display === 'flex') {
                    closeModalFunc();
                }
            });

            async function loadUserMetrics() {
                try {
                    const response = await fetch('/api/admin/users/' + userId + '/metrics', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-API-KEY': apiKey,
                            'X-MY-ORIGIN': origin
                        },
                        credentials: 'same-origin'
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(
                            result.message || 'Failed to load user metrics.'
                        );
                    }

                    const role = result.role;
                    const metrics = result.metrics;
                    const profileMetrics = document.getElementById('profileMetrics');

                    if (role === 'client') {
                        profileMetrics.style.display = 'flex';

                        document.getElementById('profile-card-pending').style.display = 'flex';
                        document.getElementById('profile-card-accepted').style.display = 'flex';
                        document.getElementById('profile-card-in-progress').style.display = 'flex';
                        document.getElementById('profile-card-awaiting-confirmation').style.display = 'flex';
                        document.getElementById('profile-card-dispute').style.display = 'flex';
                        document.getElementById('profile-card-total-active').style.display = 'flex';
                        document.getElementById('profile-card-total-history').style.display = 'flex';
                        document.getElementById('profile-card-completed').style.display = 'flex';
                        document.getElementById('profile-card-failed').style.display = 'flex';

                        document.getElementById('profile-metric-pending').textContent = metrics.pending;
                        document.getElementById('profile-metric-accepted').textContent = metrics.accepted;
                        document.getElementById('profile-metric-in-progress').textContent = metrics.in_progress;
                        document.getElementById('profile-metric-awaiting-confirmation').textContent = metrics.awaiting_confirmation;
                        document.getElementById('profile-metric-dispute').textContent = metrics.dispute;
                        document.getElementById('profile-metric-total-active').textContent = metrics.total_active;
                        document.getElementById('profile-metric-total-history').textContent = metrics.total_history;
                        document.getElementById('profile-metric-completed').textContent = metrics.completed;
                        document.getElementById('profile-metric-failed').textContent = metrics.failed;

                    } else if (role === 'tasker') {
                        profileMetrics.style.display = 'flex';

                        document.getElementById('profile-card-accepted').style.display = 'flex';
                        document.getElementById('profile-card-in-progress').style.display = 'flex';
                        document.getElementById('profile-card-awaiting-confirmation').style.display = 'flex';
                        document.getElementById('profile-card-dispute').style.display = 'flex';
                        document.getElementById('profile-card-total-active').style.display = 'flex';
                        document.getElementById('profile-card-total-history').style.display = 'flex';
                        document.getElementById('profile-card-completed').style.display = 'flex';
                        document.getElementById('profile-card-failed').style.display = 'flex';
                        document.getElementById('profile-card-income').style.display = 'flex';

                        document.getElementById('profile-metric-accepted').textContent = metrics.accepted;
                        document.getElementById('profile-metric-in-progress').textContent = metrics.in_progress;
                        document.getElementById('profile-metric-awaiting-confirmation').textContent = metrics.awaiting_confirmation;
                        document.getElementById('profile-metric-dispute').textContent = metrics.dispute;
                        document.getElementById('profile-metric-total-active').textContent = metrics.total_active;
                        document.getElementById('profile-metric-total-history').textContent = metrics.total_history;
                        document.getElementById('profile-metric-completed').textContent = metrics.completed;
                        document.getElementById('profile-metric-failed').textContent = metrics.failed;
                        document.getElementById('profile-metric-income').textContent = metrics.income;

                    } else if (role === 'admin') {
                        profileMetrics.style.display = 'none';
                        profileMetrics.setAttribute('data-print', 'hidden');
                    }

                } catch (error) {
                    console.error('User metrics error:', error);

                    errorMessage.textContent =
                        error.message || 'Unable to load user metrics.';

                    errorMessage.style.display = 'block';
                }
            }

            try {
                const response = await fetch('/api/admin/users/' + userId, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-KEY': apiKey,
                        'X-MY-ORIGIN': origin
                    },
                    credentials: 'same-origin',
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Failed to load profile information.'
                    );
                }

                user = result.user;

                document.getElementById('profile-id').textContent = user.id;
                document.getElementById('profile-email').textContent = user.email;
                document.getElementById('profile-name').textContent = user.full_name;
                document.getElementById('profile-last-name').textContent = user.last_name;
                document.getElementById('profile-first-name').textContent = user.first_name;
                document.getElementById('profile-middle-name').textContent = user.middle_name || '-';
                document.getElementById('profile-contact-number').textContent = user.contact_number;
                document.getElementById('profile-created-at').textContent = user.created_at;

                const roleElement = document.getElementById('profile-role');

                roleElement.textContent = user.formatted_role;
                roleElement.classList.add(user.role_class);

                if (user.role === 'admin') {
                    document.getElementById('btn-delete-account').style.display = 'none';
                    document.getElementById('admin-restricted-msg').style.display = 'block';
                }

                profilePhoto.onload = function () {
                    profilePhotoSpinner.style.display = 'none';
                    profilePhoto.style.display = 'block';
                };

                profilePhoto.onerror = function () {
                    profilePhotoSpinner.style.display = 'none';
                    profilePhoto.style.display = 'block';
                };

                profilePhoto.src = user.profile_picture;

            } catch (error) {
                console.error('Profile error:', error);

                errorMessage.textContent =
                    error.message || 'Unable to load profile information.';

                errorMessage.style.display = 'block';
            }

            await loadUserMetrics();

            const deleteAccountBtn = document.getElementById('btn-delete-account');

            if (deleteAccountBtn) {
                deleteAccountBtn.addEventListener('click', async function() {
                    if (!user) return;

                    const fullName = user.first_name +
                        (user.middle_name ? ' ' + user.middle_name : '') +
                        ' ' + user.last_name;

                    const customTitle = 'Confirm Account Deletion';
                    const customMessage = 'Are you sure you want to delete the account of "' + fullName + '" (ID: ' + user.id + ')? The account and associated data will be permanently deleted.';
                    const isConfirmed = await showConfirmation(customTitle, customMessage);

                    if (!isConfirmed) return;

                    try {
                        const response = await fetch('/api/admin/users/' + user.id + '/delete', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'X-API-KEY': apiKey,
                                'X-MY-ORIGIN': origin
                            },
                            credentials: 'same-origin'
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(
                                result.message || 'Failed to delete user.'
                            );
                        }

                        sessionStorage.setItem('success_message', result.message);
                        window.location.href = '{{ route("admin.manage-users") }}';

                    } catch (error) {
                        console.error('Error:', error);

                        errorMessage.textContent =
                            error.message || 'Unable to delete the user.';

                        errorMessage.style.display = 'block';
                    }
                });
            }
        });
    </script>
@endpush