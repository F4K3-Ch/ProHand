@extends('layouts.app')

@section('title', 'My Profile - ProHand')

@section('sidebar')
    @include('layouts.sidebar.' . auth()->user()->role)
@endsection

@section('content')
    <a href="{{ route(auth()->user()->role . '.dashboard') }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <main class="view-main">
        <div class="profile-info-container">
            <div class="profile-container">
                <h1>My Profile</h1>

                <div class="view-main-error-success-message">
                    @if(session('success'))
                        <p class="success" id="sessionSuccessMessage">{{ session('success') }}</p>
                    @endif

                    @if(session('error'))
                        <p class="error" id="sessionErrorMessage">{{ session('error') }}</p>
                    @endif

                    <p class="success" id="successMessageText" style="display: none;"></p>
                    <p class="error" id="errorMessageText" style="display: none;"></p>
                </div>

                <div class="profile-photo-section">
                    <div class="photo-wrapper">
                        <img
                            id="profile-preview-photo"
                            class="profile-preview-photo"
                            src=""
                            alt="Profile Picture"
                            style="display: none;"
                        >

                        <div
                            id="profile-photo-spinner"
                            class="circle-spinner"
                            style="width: 110px; height: 110px; border-width: 4px;"
                        ></div>
                    </div>
                </div>

                <div class="profile-bot-container">
                    <div class="profile-box">
                        <label>ID</label>
                        <span id="profile-id">Loading...</span>
                    </div>

                    <div class="profile-box">
                        <label>Email</label>
                        <span id="profile-email">Loading...</span>
                    </div>

                    <div class="group-profile-box">
                        <div class="profile-box">
                            <label>Last Name</label>
                            <span id="profile-last-name">Loading...</span>
                        </div>

                        <div class="profile-box">
                            <label>First Name</label>
                            <span id="profile-first-name">Loading...</span>
                        </div>

                        <div class="profile-box">
                            <label>Middle Name</label>
                            <span id="profile-middle-name">Loading...</span>
                        </div>
                    </div>

                    <div class="profile-box">
                        <label>Role</label>
                        <span id="profile-role" class="role-field">Loading...</span>
                    </div>

                    <div class="profile-box">
                        <label>Contact Number</label>
                        <span id="profile-contact-number">Loading...</span>
                    </div>

                    <div class="profile-box">
                        <label>Created At</label>
                        <span id="profile-created-at">Loading...</span>
                    </div>

                    <div class="profile-bot-btn">
                        <button type="button" id="btn-edit-profile" class="btn-edit-profile">
                            <i class="fa-regular fa-pen-to-square"></i>
                            Edit Profile
                        </button>

                        <button type="button" id="btn-change-password" class="btn-change-password">
                            <i class="fa-solid fa-key"></i>
                            Change Password
                        </button>

                        <button type="button" id="btn-delete-account" class="btn-delete-account">
                            <i class="fa-regular fa-trash-can"></i>
                            Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="profilePhotoModal" class="profile-modal-overlay" style="display: none;">
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
            const apiKey = "{{ config('services.my_api.api_key') }}";
            const errorMessage = document.getElementById('errorMessageText');
            const successMessage = document.getElementById('successMessageText');
            const profilePhoto = document.getElementById('profile-preview-photo');
            const profilePhotoSpinner = document.getElementById('profile-photo-spinner');
            const photoModal = document.getElementById('profilePhotoModal');
            const modalImg = document.getElementById('modalProfileImage');
            const closePhotoBtn = document.getElementById('closePhotoModal');

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

            try {
                const response = await fetch('/api/profile/data', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-Key': apiKey,
                    },
                    credentials: 'same-origin',
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message || 'Failed to load profile information.'
                    );
                }

                const user = result.user;

                document.getElementById('profile-id').textContent = user.id;
                document.getElementById('profile-email').textContent = user.email;
                document.getElementById('profile-last-name').textContent = user.last_name;
                document.getElementById('profile-first-name').textContent = user.first_name;
                document.getElementById('profile-middle-name').textContent = user.middle_name || '-';
                document.getElementById('profile-contact-number').textContent = user.contact_number;
                document.getElementById('profile-created-at').textContent = user.created_at;

                const roleElement = document.getElementById('profile-role');
                roleElement.textContent = user.formatted_role;
                roleElement.classList.add(user.role_class);

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

            const editProfileBtn = document.getElementById('btn-edit-profile');
            if (editProfileBtn) {
                editProfileBtn.addEventListener('click', function () {
                    window.location.href = "{{ route('profile.edit') }}";
                });
            }

            const changePasswordBtn = document.getElementById('btn-change-password');
            if (changePasswordBtn) {
                changePasswordBtn.addEventListener('click', function () {
                    window.location.href = "{{ route('profile.change-password') }}";
                });
            }
        });
    </script>
@endpush