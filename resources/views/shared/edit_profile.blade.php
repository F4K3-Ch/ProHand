@extends('layouts.app')

@section('title', 'Edit Profile - ProHand')

@section('sidebar')
    @include('layouts.sidebar.' . auth()->user()->role)
@endsection

@section('content')
    <a href="{{ route('profile') }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <main class="view-main">
        <div class="edit-profile-container">
            <h1><i class="fa-solid fa-user-pen"></i> Edit Profile</h1>

            <div class="view-main-error-success-message">
                <p class="error" id="error-message" style="display: none;"></p>
                <p class="success" id="success-message" style="display: none;"></p>
            </div>

            <form id="edit-profile-form" enctype="multipart/form-data">
                @csrf

                <div class="profile-photo-section">
                    <div class="photo-wrapper">
                        <img id="profile-preview-photo" class="profile-preview-photo" src="" alt="Profile Picture" style="display: none;">
                        <div id="profile-photo-spinner" class="circle-spinner profile-avatar-spinner"></div>
                    </div>

                    <div class="photo-action-buttons">
                        <label for="profile_picture" class="btn-upload-file"><i class="fa-solid fa-camera"></i> Change Photo</label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                        <button type="button" id="btn-remove-photo" class="btn-remove-file"><i class="fa-solid fa-trash"></i> Remove</button>
                    </div>
                </div>

                <div class="profile-bot-container">
                    <div class="edit-profile-input-box">
                        <label for="profile-last-name"><i class="fa-solid fa-signature"></i> Last Name</label>
                        <input type="text" id="profile-last-name" name="last_name" class="form-input" required>
                    </div>

                    <div class="edit-profile-input-box">
                        <label for="profile-first-name"><i class="fa-solid fa-signature"></i> First Name</label>
                        <input type="text" id="profile-first-name" name="first_name" class="form-input" required>
                    </div>

                    <div class="edit-profile-input-box">
                        <label for="profile-middle-name"><i class="fa-solid fa-signature"></i> Middle Name</label>
                        <input type="text" id="profile-middle-name" name="middle_name" class="form-input">
                    </div>

                    <div class="edit-profile-input-box">
                        <label for="profile-contact-number"><i class="fa-solid fa-phone"></i> Contact Number</label>
                        <input type="text" id="profile-contact-number" name="contact_number" class="form-input" required>
                    </div>

                    <div class="profile-bot-btn">
                        <button type="submit" id="btn-save-profile" class="btn-edit-profile">
                            <i class="fa-solid fa-check"></i> Save Changes
                            <div class="circle-spinner" id="profileSpinner" style="display: none;"></div>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const errorMessage = document.getElementById('error-message');
            const successMessage = document.getElementById('success-message');
            const profilePhoto = document.getElementById('profile-preview-photo');
            const profilePhotoSpinner = document.getElementById('profile-photo-spinner');
            const profilePictureInput = document.getElementById('profile_picture');
            const btnRemovePhoto = document.getElementById('btn-remove-photo');
            const profileSpinner = document.getElementById('profileSpinner');
            const saveButton = document.getElementById('btn-save-profile');
            
            let removePictureFlag = false;

            try {
                const response = await fetch('/api/profile/data', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-KEY': apiKey,
                        'X-MY-ORIGIN' : origin
                    },
                    credentials: 'same-origin',
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load profile information.');
                }

                const user = result.user;

                document.getElementById('profile-last-name').value = user.last_name || '';
                document.getElementById('profile-first-name').value = user.first_name || '';
                document.getElementById('profile-middle-name').value = user.middle_name || '';
                document.getElementById('profile-contact-number').value = user.contact_number || '';

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
                console.error('Profile fetch error:', error);
                errorMessage.textContent = error.message || 'Unable to load profile information.';
                errorMessage.style.display = 'block';
            }

            profilePictureInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                const maxSize = 2 * 1024 * 1024;

                if (file) {
                    if (file.size > maxSize) {
                        errorMessage.textContent = 'This image is too large. Please choose a file smaller than 2MB.';
                        errorMessage.style.display = 'block';
                        this.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePhoto.src = e.target.result;
                        profilePhoto.style.display = 'block';
                        profilePhotoSpinner.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                    removePictureFlag = false;
                    errorMessage.style.display = 'none';
                }
            });

            btnRemovePhoto.addEventListener('click', function() {
                profilePictureInput.value = ''; 
                removePictureFlag = true;
                profilePhoto.src = "{{ asset('uploads/profile_pictures/default.jpg') }}";
                profilePhoto.style.display = 'block';
                profilePhotoSpinner.style.display = 'none';
            });

            const form = document.getElementById('edit-profile-form');
            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                profileSpinner.style.display = 'inline-block';
                saveButton.disabled = true;
                errorMessage.style.display = 'none';
                successMessage.style.display = 'none';

                const formData = new FormData(form);
                if (removePictureFlag) {
                    formData.append('remove_picture', '1');
                }

                try {
                    const response = await fetch('/api/profile/update', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'X-API-KEY': apiKey,
                            'X-MY-ORIGIN' : origin
                        },
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    sessionStorage.setItem('success_message', data.message);
                    window.location.href = "{{ route('profile') }}";

                } catch (err) {
                    let errMessage = 'An unexpected error occurred.';

                    if (err.errors) {
                        const firstKey = Object.keys(err.errors)[0];
                        if (firstKey && err.errors[firstKey]?.length) {
                            errMessage = err.errors[firstKey][0];
                        }
                    } else if (err.message) {
                        errMessage = err.message;
                    } else if (err.error) {
                        errMessage = err.error;
                    }

                    errorMessage.textContent = errMessage;
                    errorMessage.style.display = 'block';
                } finally {
                    profileSpinner.style.display = 'none';
                    saveButton.disabled = false;
                }
            });
        });
    </script>
@endpush