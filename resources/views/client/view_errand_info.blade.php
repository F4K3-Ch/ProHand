@extends('layouts.app')

@section('title', 'View Errand')

@section('sidebar')
    @include('layouts.sidebar.client')
@endsection

@section('content')
    <a href="{{ route(request('from', 'client.dashboard')) }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <main class="view-main" data-errand-id="{{ $id }}">
        <div class="info-container">
            <div class="errand-details-container">
                <h1>Errand Details</h1>

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

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Errand ID</label>
                        <span id="errand-id">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>Category</label>
                        <span id="errand-category">Loading...</span>
                    </div>
                </div>

                <div class="info-box">
                    <label>Title</label>
                    <span id="errand-title">Loading...</span>
                </div>

                <div class="info-box">
                    <label>Description</label>
                    <div id="errand-description" class="text-area">Loading...</div>
                </div>

                <div class="info-box">
                    <label>Location</label>
                    <span id="errand-location">Loading...</span>
                </div>

                <div class="info-box">
                    <label>Contact</label>
                    <div id="errand-contact" class="text-area">Loading...</div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Fee</label>
                        <span id="errand-fee">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>Status</label>
                        <span id="errand-status" class="status-field">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Created At</label>
                        <span id="errand-created-at">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>Last Updated</label>
                        <span id="errand-updated-at">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Accepted At</label>
                        <span id="errand-accepted-at">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>In Progress At</label> 
                        <span id="errand-in-progress-at">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Deadline</label>
                        <span id="errand-deadline">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>Finished At</label>
                        <span id="errand-finished-at">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Attachment</label>
                        <div id="errand-attachment">
                            <div class="attachment-item" style="display: none;">
                                <a href="#" target="_blank" class="attachment-name" title="Preview File">
                                    <i class="fa-solid fa-file"></i>
                                    <span class="attachment-filename"></span>
                                </a>
                                <a href="#" class="btn-download" download>
                                    <i class="fa-solid fa-download"></i>
                                    Download
                                </a>
                            </div>
                            <div class="no-attachments">
                                <p class="no-attachment">None</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-bot-btn">
                    <a href="#" id="editErrandBtn" class="btn-edit-errand" style="display: none;">
                        <i class="fa-solid fa-pen"></i> Edit Errand
                    </a>
                    <button type="button" id="deleteErrandBtn" class="btn-delete" style="display: none;">
                        <i class="fa-solid fa-trash"></i> Delete Errand
                    </button>

                    <button type="button" disabled class="btn-edit-errand-disabled" id="disabledEditBtn" style="display: none;">
                        <i class="fa-regular fa-pen-to-square"></i>Edit
                    </button>
                    <button type="button" id="cancelAcceptedBtn" class="btn-cancel" style="display: none;">
                        <i class="fa-solid fa-ban"></i>Cancel
                    </button>

                    <span class="descriptive-status-badge" id="performingBadge" style="display: none;">
                        <i class="fa-solid fa-spinner fa-spin"></i>Tasker is performing errand...
                    </span>
                    <button type="button" id="cancelRequestBtn" class="btn-cancel" style="display: none;">
                        <i class="fa-solid fa-hand-holding-hand"></i>Request Cancel
                    </button>
                    <span class="descriptive-status-badge" id="pendingCancelBadge" style="display: none;">
                        <i class="fa-solid fa-spinner fa-spin"></i>Pending Admin Cancel...
                    </span>

                    <button type="button" id="confirmCompletionBtn" class="btn-confirm-completion" style="display: none;">
                        <i class="fa-solid fa-check"></i> Confirm Completion
                    </button>
                    <button type="button" id="disputeErrandBtn" class="btn-dispute" style="display: none;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Dispute
                    </button>

                    <span id="disputeBadge" class="descriptive-status-badge" style="display: none;">
                        <i class="fa-solid fa-triangle-exclamation"></i> You raised a dispute. An admin will review the details and contact you.
                    </span>
                </div>
            </div>

            <div class="users-container" id="users-container" style="display: none;">
                <div class="user-details-container">
                    <h1>Accepted By</h1>

                    <div class="user-header-row">
                        <div class="profile-photo-section">
                            <div class="photo-wrapper">
                                <img
                                    id="tasker-profile-photo"
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
                    </div>
                    
                    <div class="info-box">
                        <label>User ID</label>
                        <span id="tasker-id">Loading...</span>
                    </div>

                    <div class="info-box">
                        <label>Email</label>
                        <span id="tasker-email">Loading...</span>
                    </div>

                    <div class="info-box">
                        <label>Name</label>
                        <span id="tasker-name">Loading...</span>
                    </div>

                    <div class="info-box">
                        <label>Contact Number</label>
                        <span id="tasker-contact-number">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const mainContainer = document.querySelector('.view-main');
            const errandId = mainContainer ? mainContainer.getAttribute('data-errand-id') : null;
            const errorMessage = document.getElementById('errorMessageText');

            if (!errandId) {
                errorMessage.textContent = 'Errand ID not found.';
                errorMessage.style.display = 'block';
                return;
            }

            try {
                const response = await fetch('/api/client/errands/' + encodeURIComponent(errandId), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-KEY': apiKey,
                        'X-MY-ORIGIN' : origin
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load errand details.');
                }

                const errand = result.errand;

                document.getElementById('errand-id').textContent = errand.id;
                document.getElementById('errand-category').textContent = errand.category;
                document.getElementById('errand-title').textContent = errand.title;
                document.getElementById('errand-description').textContent = errand.description || 'None';
                document.getElementById('errand-location').textContent = errand.location;
                document.getElementById('errand-contact').textContent = errand.contact || 'None';
                document.getElementById('errand-fee').textContent = errand.fee;
                
                const statusElement = document.getElementById('errand-status');
                statusElement.textContent = errand.status;
                statusElement.className = errand.status_class;

                document.getElementById('errand-created-at').textContent = errand.created_at;
                document.getElementById('errand-updated-at').textContent = errand.updated_at;
                document.getElementById('errand-accepted-at').textContent = errand.accepted_at;
                document.getElementById('errand-in-progress-at').textContent = errand.in_progress_at;
                document.getElementById('errand-deadline').textContent = errand.deadline;
                document.getElementById('errand-finished-at').textContent = errand.finished_at;

                const usersContainer = document.getElementById('users-container');
                const profilePhoto = document.getElementById('tasker-profile-photo');
                const photoSpinner = document.getElementById('profile-photo-spinner');

                if (errand.tasker) {
                    usersContainer.style.display = 'flex';
                    
                    document.getElementById('tasker-id').textContent = errand.tasker.id;
                    document.getElementById('tasker-email').textContent = errand.tasker.email;
                    document.getElementById('tasker-name').textContent = errand.tasker.name;
                    document.getElementById('tasker-contact-number').textContent = errand.tasker.contact_number;

                    if (errand.tasker.profile_picture) {
                        profilePhoto.src = errand.tasker.profile_picture;
                        profilePhoto.onload = function() {
                            photoSpinner.style.display = 'none';
                            profilePhoto.style.display = 'flex';
                        };
                        profilePhoto.onerror = function() {
                            photoSpinner.style.display = 'none';
                        };
                    } else {
                        photoSpinner.style.display = 'none';
                    }
                } else {
                    usersContainer.style.display = 'none';
                }

                const attachContainer = document.getElementById('errand-attachment');
                const attachItem = attachContainer.querySelector('.attachment-item');
                const noAttachItem = attachContainer.querySelector('.no-attachments');

                if (errand.attachment) {
                    attachItem.style.display = 'flex';
                    noAttachItem.style.display = 'none';
                    attachItem.querySelector('.attachment-filename').textContent = errand.attachment.name;
                    attachItem.querySelector('.attachment-name').href = errand.attachment.url;
                    
                    const downloadBtn = attachItem.querySelector('.btn-download');
                    downloadBtn.href = errand.attachment.url;
                    downloadBtn.setAttribute('download', errand.attachment.name);
                }

                if (errand.raw_status === 'pending') {
                    const editBtn = document.getElementById('editErrandBtn');
                    const deleteBtn = document.getElementById('deleteErrandBtn');

                    editBtn.href =
                        '/client/edit-errand/' + encodeURIComponent(errand.id) +
                        '?from=client.dashboard';
                    editBtn.style.display = 'flex';
                    deleteBtn.style.display = 'flex';

                    deleteBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Deletion';
                        const customMessage = 'Are you sure you want to delete errand #' + errand.id + ' ("' + errand.title + '")? It will be deleted permanently.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;

                        try {
                            const response = await fetch('/api/client/errands/' + errand.id + '/delete', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN' : origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            if (!response.ok || !result.success) throw new Error(result.message || 'Failed to delete errand.');

                            sessionStorage.setItem('success_message', result.message || 'Errand deleted successfully!');
                            window.location.href = '{{ route("client.dashboard") }}';

                        } catch(error) {
                            console.error('Delete error:', error);
                            const errorText = document.getElementById('errorMessageText');
                            errorText.textContent = error.message;
                            errorText.style.display = 'block';
                            setTimeout(() => { errorText.style.display = 'none'; }, 4000);
                        }
                    });

                } else if (errand.raw_status === 'accepted') {
                    document.getElementById('disabledEditBtn').style.display = 'flex';
                    const cancelAcceptedBtn = document.getElementById('cancelAcceptedBtn');
                    cancelAcceptedBtn.style.display = 'flex';

                    cancelAcceptedBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Cancellation';
                        const customMessage = 'Are you sure you want to cancel this errand? It will return to "Pending" status.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        // Add your cancel pending payment AJAX call implementation here
                    });

                } else if (errand.raw_status === 'in_progress') {
                    if (Number(errand.cancel_requested_by_client) === 0) {
                        document.getElementById('performingBadge').style.display = 'flex';
                        const cancelRequestBtn = document.getElementById('cancelRequestBtn');
                        cancelRequestBtn.style.display = 'flex';

                        cancelRequestBtn.addEventListener('click', async function() {
                            const customTitle = 'Confirm Cancellation Request';
                            const customMessage = 'Are you sure you want to send a cancellation request to the Admin? It will return to "Pending" status and this action cannot be undone until processed.';
                            const isConfirmed = await showConfirmation(customTitle, customMessage);
                            
                            if(!isConfirmed) return;
                            // Add your client cancel request AJAX call implementation here

                        });
                    } else {
                        document.getElementById('pendingCancelBadge').style.display = 'flex';
                    }
                } else if (errand.raw_status === 'awaiting_confirmation') {
                    const confirmCompletionBtn = document.getElementById('confirmCompletionBtn');
                    const disputeErrandBtn = document.getElementById('disputeErrandBtn');

                    confirmCompletionBtn.style.display = 'flex';
                    disputeErrandBtn.style.display = 'flex';

                    confirmCompletionBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Completion';
                        const customMessage = 'Are you sure you want to confirm the completion of errand #' + errand.id + ' ("' + errand.title + '")? This action cannot be undone.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;

                        try {
                            const response = await fetch('/api/client/errands/' + errand.id + '/complete', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN' : origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            if (!response.ok || !result.success) throw new Error(result.message || 'Failed to mark complete errand.');

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("client.history") }}';

                        } catch(error) {
                            console.error('Delete error:', error);
                            const errorText = document.getElementById('errorMessageText');
                            errorText.textContent = error.message;
                            errorText.style.display = 'block';
                            setTimeout(() => { errorText.style.display = 'none'; }, 4000);
                        }
                    });

                    disputeErrandBtn.addEventListener('click', async function() {
                        const customTitle = 'Dispute Errand';
                        const customMessage = 'Are you sure you want to dispute errand #' + errand.id + ' ("' + errand.title + '")? This action will notify the Admin for review.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;

                        try {
                            const response = await fetch('/api/client/errands/' + errand.id + '/dispute', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN' : origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            if (!response.ok || !result.success) throw new Error(result.message || 'Failed to dispute errand.');

                            sessionStorage.setItem('success_message', result.message);
                            window.location.reload();

                        } catch(error) {
                            console.error('Delete error:', error);
                            const errorText = document.getElementById('errorMessageText');
                            errorText.textContent = error.message;
                            errorText.style.display = 'block';
                            setTimeout(() => { errorText.style.display = 'none'; }, 4000);
                        }
                    });

                } else if (errand.raw_status === 'dispute') {
                    const disputeBadge = document.getElementById('disputeBadge');
                    disputeBadge.style.display = 'flex';
                } else if (errand.raw_status === 'completed' || errand.raw_status === 'failed') {
                    const deleteBtn = document.getElementById('deleteErrandBtn');
                    deleteBtn.style.display = 'flex';

                    deleteBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Deletion';
                        const customMessage = 'Are you sure you want to delete errand #' + errand.id + ' ("' + errand.title + '")? It will be deleted permanently.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;

                        // Add your delete completed/failed errand AJAX call implementation here
                    });
                }

            } catch (error) {
                console.error('Errand details error:', error);
                errorMessage.textContent = error.message || 'Unable to load errand information.';
                errorMessage.style.display = 'flex';
            }
        });
    </script>
@endpush