@extends('layouts.app')

@section('title', 'View Errand')

@section('sidebar')
    @include('layouts.sidebar.admin')
@endsection

@section('content')
    <a href="{{ route(request('from', 'admin.dashboard')) }}" class="btn-back">
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

                    <p class="success" id="successMessageText" style="display:none;"></p>
                    <p class="error" id="errorMessageText" style="display:none;"></p>
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
                            <div class="attachment-item" style="display:none;">
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
                    <button type="button" id="deleteErrandBtn" class="btn-delete" style="display: none;">
                        <i class="fa-solid fa-trash"></i> Delete Errand
                    </button>

                    <span id="performingBadge" class="descriptive-status-badge" style="display:none;">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Tasker is performing errand...
                    </span>

                    <button type="button" id="confirmCompletionBtn" class="btn-confirm-completion" style="display: none;">
                        <i class="fa-solid fa-check"></i> Confirm Completion
                    </button>

                    <button type="button" id="completeDisputeBtn" class="btn-approve" style="display:none;">
                        <i class="fa-solid fa-circle-check"></i> Mark as Completed
                    </button>

                    <button type="button" id="failDisputeBtn" class="btn-delete" style="display:none;">
                        <i class="fa-solid fa-circle-xmark"></i> Mark as Failed
                    </button>

                    <button type="button" id="cancelBtn" class="btn-cancel" style="display: none;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Force Cancel
                    </button>
                </div>
            </div>

            <div class="users-container" id="users-container">
                <div class="user-details-container">
                    <h1>Posted By</h1>

                    <div class="user-header-row">
                        <div class="profile-photo-section">
                            <div class="photo-wrapper">
                                <img
                                    id="client-profile-photo"
                                    class="profile-preview-photo"
                                    src=""
                                    alt="Profile Picture"
                                    style="display:none;"
                                >

                                <div
                                    id="client-profile-photo-spinner"
                                    class="circle-spinner"
                                    style="width:110px;height:110px;border-width:4px;"
                                ></div>
                            </div>
                        </div>

                        <div
                            class="user-cancellation-alert-box"
                            id="client-cancellation-box"
                            style="display:none;"
                        >
                            <p class="alert-title">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Requesting Cancellation
                            </p>

                            <div class="alert-action-buttons">
                                <button
                                    type="button"
                                    class="btn-approve-release"
                                    data-requester="client"
                                >
                                    <i class="fa-solid fa-user-minus"></i> Approve
                                </button>

                                <button
                                    type="button"
                                    class="btn-deny-release"
                                    data-requester="client"
                                >
                                    <i class="fa-solid fa-user-check"></i> Deny
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>User ID</label>
                            <span id="client-id">Loading...</span>
                        </div>

                        <div class="info-box">
                            <label>Email</label>
                            <span id="client-email">Loading...</span>
                        </div>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>Name</label>
                            <span id="client-name">Loading...</span>
                        </div>

                        <div class="info-box">
                            <label>Contact Number</label>
                            <span id="client-contact-number">Loading...</span>
                        </div>
                    </div>
                </div>

                <div class="user-details-container" id="tasker-container" style="display:none;">
                    <h1>Accepted By</h1>

                    <div class="user-header-row">
                        <div class="profile-photo-section">
                            <div class="photo-wrapper">
                                <img
                                    id="tasker-profile-photo"
                                    class="profile-preview-photo"
                                    src=""
                                    alt="Profile Picture"
                                    style="display:none;"
                                >

                                <div
                                    class="circle-spinner"
                                    style="width:110px;height:110px;border-width:4px;"
                                ></div>
                            </div>
                        </div>

                        <div
                            class="user-cancellation-alert-box"
                            id="tasker-cancellation-box"
                            style="display:none;"
                        >
                            <p class="alert-title">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Requesting Cancellation
                            </p>

                            <div class="alert-action-buttons">
                                <button
                                    type="button"
                                    class="btn-approve-release"
                                    data-requester="tasker"
                                >
                                    <i class="fa-solid fa-user-minus"></i> Approve
                                </button>

                                <button
                                    type="button"
                                    class="btn-deny-release"
                                    data-requester="tasker"
                                >
                                    <i class="fa-solid fa-user-check"></i> Deny
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>User ID</label>
                            <span id="tasker-id">Loading...</span>
                        </div>

                        <div class="info-box">
                            <label>Email</label>
                            <span id="tasker-email">Loading...</span>
                        </div>
                    </div>

                    <div class="group-info-box">
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
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const mainContainer = document.querySelector('.view-main');
            const errandId = mainContainer ? mainContainer.getAttribute('data-errand-id') : null;
            const errorMessageText = document.getElementById('errorMessageText');
            
            function displayGlobalError(message){
                if(!errorMessageText) return;
                errorMessageText.textContent = message;
                errorMessageText.style.display = 'flex';
                setTimeout(() => { 
                    errorMessageText.style.display = 'none'; 
                }, 5000);
            }

            try {
                const response = await fetch('/api/admin/errands/' + encodeURIComponent(errandId), {
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
                    throw new Error(result.message || 'Failed to load errand details.');
                }

                const errand = result.errand;

                // Populate text fields
                document.getElementById('errand-id').textContent = errand.id;
                document.getElementById('errand-category').textContent = errand.category;
                document.getElementById('errand-title').textContent = errand.title;
                document.getElementById('errand-description').textContent = errand.description;
                document.getElementById('errand-location').textContent = errand.location;
                document.getElementById('errand-contact').textContent = errand.contact;
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
                const taskerContainer = document.getElementById('tasker-container');

                usersContainer.style.display = 'flex';

                /* Client */
                const client = errand.client;
                const clientPhoto = document.getElementById('client-profile-photo');
                const clientSpinner = document.getElementById('client-profile-photo-spinner');

                if (client) {
                    document.getElementById('client-id').textContent = client.id;
                    document.getElementById('client-email').textContent = client.email;
                    document.getElementById('client-name').textContent = client.name;
                    document.getElementById('client-contact-number').textContent = client.contact_number;

                    if (client.profile_picture) {
                        clientPhoto.src = client.profile_picture;

                        clientPhoto.onload = function () {
                            clientSpinner.style.display = 'none';
                            clientPhoto.style.display = 'flex';
                        };

                        clientPhoto.onerror = function () {
                            clientSpinner.style.display = 'none';
                            clientPhoto.style.display = 'none';
                        };
                    } else {
                        clientSpinner.style.display = 'none';
                    }

                    const clientCancelBox = document.getElementById('client-cancellation-box');

                    if (errand.cancel_requested_by_client) {
                        clientCancelBox.style.display = 'flex';
                    }
                } else {
                    document.getElementById('client-id').textContent = 'None';
                    document.getElementById('client-email').textContent = 'None';
                    document.getElementById('client-name').textContent = 'None';
                    document.getElementById('client-contact-number').textContent = 'None';
                    clientSpinner.style.display = 'none';
                }

                /* Tasker */
                if (errand.tasker) {
                    const tasker = errand.tasker;

                    taskerContainer.style.display = 'block';

                    document.getElementById('tasker-id').textContent = tasker.id;
                    document.getElementById('tasker-email').textContent = tasker.email;
                    document.getElementById('tasker-name').textContent = tasker.name;
                    document.getElementById('tasker-contact-number').textContent = tasker.contact_number;

                    const taskerPhoto = document.getElementById('tasker-profile-photo');
                    const taskerSpinner = taskerContainer.querySelector('.circle-spinner');

                    if (tasker.profile_picture) {
                        taskerPhoto.src = tasker.profile_picture;

                        taskerPhoto.onload = function () {
                            taskerSpinner.style.display = 'none';
                            taskerPhoto.style.display = 'flex';
                        };

                        taskerPhoto.onerror = function () {
                            taskerSpinner.style.display = 'none';
                            taskerPhoto.style.display = 'none';
                        };
                    } else {
                        taskerSpinner.style.display = 'none';
                    }

                    const taskerCancelBox = document.getElementById('tasker-cancellation-box');

                    if (errand.cancel_requested_by_tasker) {
                        taskerCancelBox.style.display = 'flex';
                    }
                } else {
                    taskerContainer.style.display = 'none';
                }
        
                document.querySelectorAll('.btn-approve-release, .btn-deny-release').forEach(button => {
                    button.addEventListener('click', async function () {
                        const requesterType = this.getAttribute('data-requester');
                        const isApprove = this.classList.contains('btn-approve-release');
                        
                        const actionText = isApprove ? 'approve' : 'deny';
                        const customTitle = (isApprove ? 'Approve' : 'Deny') + ' Cancellation';
                        const customMessage = 'Are you sure you want to ' + actionText + ' the cancellation request from this ' + requesterType + '?';
                        
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        if (!isConfirmed) return;

                        const endpoint = isApprove 
                            ? '/api/admin/errands/' + errand.id + '/approve-cancellation' 
                            : '/api/admin/errands/' + errand.id + '/deny-cancellation';

                        try {
                            const response = await fetch(endpoint, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN': origin
                                },
                                body: JSON.stringify({ requester: requesterType }),
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to process cancellation request.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.reload();

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                });

                // Handle Attachments
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
                    const deleteBtn = document.getElementById('deleteErrandBtn');

                    deleteBtn.style.display = 'flex';
                    deleteBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Deletion';
                        const customMessage = 'Are you sure you want to delete errand #' + errand.id + ' ("' + errand.title + '")? It will be deleted permanently.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;

                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/delete', {
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
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.dashboard") }}';

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                    
                } else if (errand.raw_status === 'accepted') {
                    cancelBtn.style.display = 'flex';
                    cancelBtn.addEventListener('click', async function(){
                        const customTitle = 'Confirm Cancellation';
                        const customMessage = 'Force-cancel this errand? The errand status will be reset back to "Pending" status.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/reset-to-pending', {
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
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                } else if (errand.raw_status === 'in_progress') {
                    document.getElementById('performingBadge').style.display = 'flex';
                    cancelBtn.style.display = 'flex';
                    cancelBtn.addEventListener('click', async function(){
                        const customTitle = 'Confirm Cancellation';
                        const customMessage = 'Force-cancel this errand? The errand status will be reset back to "Pending" status.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/reset-to-pending', {
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
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                } else if (errand.raw_status === 'awaiting_confirmation') {
                    const confirmCompletionBtn = document.getElementById('confirmCompletionBtn');
                    confirmCompletionBtn.style.display = 'flex';

                    confirmCompletionBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Completion';
                        const customMessage = 'Are you sure you want to confirm the completion of errand #' + errand.id + ' ("' + errand.title + '")? This action cannot be undone.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/completed', {
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
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.history") }}';

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                    cancelBtn.style.display = 'flex';
                    cancelBtn.addEventListener('click', async function(){
                        const customTitle = 'Confirm Cancellation';
                        const customMessage = 'Force-cancel this errand? The errand status will be reset back to "Pending" status.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/reset-to-pending', {
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
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                } else if (errand.raw_status === 'dispute') {
                    const completeDisputeBtn = document.getElementById('completeDisputeBtn');
                    const failDisputeBtn = document.getElementById('failDisputeBtn');

                    completeDisputeBtn.style.display = 'flex';
                    completeDisputeBtn.addEventListener('click', async function(){
                        const customTitle = 'Confirm Completion';
                        const customMessage = 'Mark this disputed errand as "Completed"? This will resolve the dispute and finish the errand. This action cannot be undone.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/completed', {
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
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.history") }}';

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                    failDisputeBtn.style.display = 'flex';
                    failDisputeBtn.addEventListener('click', async function(){
                        const customTitle = 'Confirm Failure';
                        const customMessage = 'Mark this disputed errand as "Failed"? This will resolve the dispute and mark the errand as failed. This action cannot be undone.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/failed', {
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
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.history") }}';

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                } else if (errand.raw_status === 'completed' || errand.raw_status === 'failed') {
                    const deleteBtn = document.getElementById('deleteErrandBtn');
                    deleteBtn.style.display = 'flex';

                    deleteBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Deletion';
                        const customMessage = 'Are you sure you want to delete errand #' + errand.id + ' ("' + errand.title + '")? It will be deleted permanently.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;

                        try {
                            const response = await fetch('/api/admin/history/' + errand.id + '/delete', {
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
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.history") }}';

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                }

            } catch (error) {
                console.error('Error:', error);
                displayGlobalError(error.message);
            }
        });
    </script>
@endpush