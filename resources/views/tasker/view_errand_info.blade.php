@extends('layouts.app')

@section('title', 'View Errand')

@section('sidebar')
    @include('layouts.sidebar.tasker')
@endsection

@section('content')
    <a href="{{ route(request('from', 'tasker.dashboard')) }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <div class="print-button-container">
        <button type="button" id="printErrandBtn" class="btn-print">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>

    <main class="view-main" data-errand-id="{{ $id }}">
        <div class="info-container">
            <div class="errand-details-container">

                <div class="errand-info-header">
                    <h1><i class="fa-solid fa-circle-info"></i> Errand Info</h1>
                </div>

                <div class="errand-card-top">
                    <span class="errand-id-badge">ID: <span id="errand-id">Loading...</span></span>
                    <span id="errand-status">Loading...</span>
                </div>

                <h2 class="errand-title" id="errand-title">Loading...</h2>

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

                <div class="errand-meta-list">
                    <div class="detail-row">
                        <span><i class="fa-solid fa-folder-open"></i> Category</span>
                        <span id="errand-category" class="errand-val">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-location-dot"></i> Location</span>
                        <span id="errand-location" class="errand-val">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-peso-sign"></i> Fee</span>
                        <strong id="errand-fee" class="errand-fee">Loading...</strong>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-credit-card"></i> Payment Method</span>
                        <span id="payment-method" class="errand-val">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-wallet"></i> Payment Status</span>
                        <span id="payment-status" class="status-field">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-calendar-check"></i> Paid At</span>
                        <span id="payment-paid-at" class="errand-val">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-calendar-plus"></i> Created At</span>
                        <span id="errand-created-at" class="errand-val">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-clock"></i> Deadline</span>
                        <span id="errand-deadline" class="errand-val">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-rotate"></i> Last Updated</span>
                        <span id="errand-updated-at" class="errand-val">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-user-check"></i> Accepted At</span>
                        <span id="errand-accepted-at" class="errand-val">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-person-running"></i> In Progress At</span>
                        <span id="errand-in-progress-at" class="errand-val">Loading...</span>
                    </div>

                    <div class="detail-row">
                        <span><i class="fa-solid fa-flag-checkered"></i> Finished At</span>
                        <span id="errand-finished-at" class="errand-val">Loading...</span>
                    </div>
                </div>

                <div class="errand-text-sections">
                    <div class="text-section-box">
                        <label><i class="fa-solid fa-align-left"></i> Description</label>
                        <div id="errand-description" class="text-area">Loading...</div>
                    </div>

                    <div class="text-section-box">
                        <label><i class="fa-solid fa-address-book"></i> Contact</label>
                        <div id="errand-contact" class="text-area">Loading...</div>
                    </div>
                </div>

                <div class="errand-attachment-section">
                    <label><i class="fa-solid fa-paperclip"></i> Attachment</label>

                    <div id="errand-attachment">
                        <div class="attachment-item" style="display:none;">
                            <a href="#" target="_blank" class="attachment-name" title="Preview File">
                                <i class="fa-solid fa-file"></i>
                                <span class="attachment-filename"></span>
                            </a>

                            <a href="#" class="btn-download" download>
                                <i class="fa-solid fa-download"></i> Download
                            </a>
                        </div>

                        <div class="no-attachments">
                            <p class="no-attachment">None</p>
                        </div>
                    </div>
                </div>

                <div class="users-container" id="users-container">
                    <div class="user-details-container">
                        <h2>Posted By</h2>

                        <div class="user-main-content">
                            <div class="user-profile-card-layout">
                                <div class="profile-photo-section">
                                    <div class="photo-wrapper">
                                        <img id="client-profile-photo" class="profile-preview-photo" src="" alt="Profile Picture" style="display:none;">
                                        <div id="profile-photo-spinner" class="circle-spinner"></div>
                                    </div>
                                </div>

                                <div class="user-info-rows">
                                    <div class="detail-row">
                                        <span>User ID</span>
                                        <span id="client-id" class="errand-val">Loading...</span>
                                    </div>

                                    <div class="detail-row">
                                        <span>Email</span>
                                        <span id="client-email" class="errand-val">Loading...</span>
                                    </div>

                                    <div class="detail-row">
                                        <span>Name</span>
                                        <span id="client-name" class="errand-val">Loading...</span>
                                    </div>

                                    <div class="detail-row">
                                        <span>Contact Number</span>
                                        <span id="client-contact-number" class="errand-val">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-bot-btn">
                    <button type="button" class="btn-accept" id="acceptErrandBtn" style="display:none;">
                        <i class="fa-solid fa-check"></i> Accept
                    </button>

                    <button type="button" id="completeErrandBtn" class="btn-complete-errand" style="display:none;">
                        <i class="fa-solid fa-check"></i> Mark as Finished
                    </button>

                    <span class="descriptive-status-badge" id="pendingCancelBadge" style="display:none;">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Waiting for Admin Decision on your Cancellation request...
                    </span>

                    <div class="badge-button-column" id="paymentContainer" style="display:none;">
                        <span class="descriptive-status-badge" id="waitingPaymentVerificationBadge" style="display:none;">
                            <i class="fa-solid fa-hourglass-half"></i>
                            Waiting for Admin Verification of Payment
                        </span>
                    </div>

                    <span class="descriptive-status-badge" id="awaitingClientConfirmationBadge" style="display:none;">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                        Awaiting client confirmation
                    </span>

                    <button type="button" id="cancelRequestBtn" class="btn-cancel" style="display:none;">
                        <i class="fa-solid fa-hand-holding-hand"></i> Request Cancel
                    </button>

                    <span class="descriptive-status-badge" id="disputeBadge" style="display:none;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        The client raised a dispute on your submission. An admin is reviewing the case and will contact you shortly.
                    </span>

                    <button type="button" id="deleteErrandBtn" class="btn-delete" style="display:none;">
                        <i class="fa-solid fa-trash"></i> Delete Errand
                    </button>
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

            const printErrandBtn = document.getElementById('printErrandBtn');

            if (printErrandBtn) {
                printErrandBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            try {
                const response = await fetch('/api/tasker/errands/' + errandId, {
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

                document.getElementById('payment-method').textContent = errand.payment.method;

                const paymentStatusElement = document.getElementById('payment-status');
                paymentStatusElement.textContent = errand.payment.status;
                paymentStatusElement.className = errand.payment.status_class;

                document.getElementById('payment-paid-at').textContent = errand.payment.paid_at;
                document.getElementById('errand-created-at').textContent = errand.created_at;
                document.getElementById('errand-updated-at').textContent = errand.updated_at;
                document.getElementById('errand-accepted-at').textContent = errand.accepted_at;
                document.getElementById('errand-in-progress-at').textContent = errand.in_progress_at;
                document.getElementById('errand-deadline').textContent = errand.deadline;
                document.getElementById('errand-finished-at').textContent = errand.finished_at;

                // Handle Tasker Section Visibility & Data
                const usersContainer = document.getElementById('users-container');
                const profilePhoto = document.getElementById('client-profile-photo');
                const photoSpinner = document.getElementById('profile-photo-spinner');

                usersContainer.style.display = 'flex';
                
                document.getElementById('client-id').textContent = errand.client.id;
                document.getElementById('client-email').textContent = errand.client.email;
                document.getElementById('client-name').textContent = errand.client.name;
                document.getElementById('client-contact-number').textContent = errand.client.contact_number;

                if (errand.client.profile_picture) {
                    profilePhoto.src = errand.client.profile_picture;
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
                    const acceptBtn = document.getElementById('acceptErrandBtn');
                    acceptBtn.style.display = 'flex';

                    acceptBtn.addEventListener('click', async function () {
                        const customTitle = 'Confirm Acceptance';
                        const customMessage = 'Are you sure you want to accept errand #' + errand.id + ' ("' + errand.title + '")?';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if (!isConfirmed) return;

                        try {
                            const response = await fetch('/api/tasker/errands/' + errand.id + '/accept', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN': origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to accept errand.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            // window.location.href = '{{ route("tasker.dashboard") }}';
                            window.location.href = '/tasker/view-errand/' + errand.id + '?from=tasker.dashboard';

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'accepted') {
                    const paymentContainer = document.getElementById('paymentContainer');
                    const waitingPaymentVerificationBadge = document.getElementById('waitingPaymentVerificationBadge');

                    paymentContainer.style.display = 'flex';

                    if (Number(errand.cancel_requested_by_tasker) === 0) {
                        waitingPaymentVerificationBadge.style.display = 'flex';
                        cancelRequestBtn.style.display = 'flex';
                        pendingCancelBadge.style.display = 'none';

                        cancelRequestBtn.addEventListener('click', async function() {
                            const customTitle = 'Confirm Cancellation Request';
                            const customMessage = 'Are you sure you want to request cancellation of errand #' + errand.id + ' ("' + errand.title + '")? The request will be sent to the Admin for review.';

                            const isConfirmed = await showConfirmation(customTitle, customMessage);

                            if (!isConfirmed) return;

                            try {
                                const response = await fetch('/api/tasker/errands/' + errand.id + '/request-cancel', {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    credentials: 'same-origin'
                                });

                                const result = await response.json();

                                if (!response.ok || !result.success) {
                                    throw new Error(
                                        result.message || 'Failed to submit cancellation request.'
                                    );
                                }

                                sessionStorage.setItem(
                                    'success_message',
                                    result.message || 'Cancellation request submitted successfully.'
                                );

                                window.location.href = window.location.href;

                            } catch (error) {
                                console.error('Error:', error);
                                displayGlobalError(error.message);
                            }
                        });

                    } else {
                        waitingPaymentVerificationBadge.style.display = 'none';
                        cancelRequestBtn.style.display = 'none';
                        pendingCancelBadge.style.display = 'flex';
                    }

                } else if (errand.raw_status === 'in_progress') {
                    const completeErrandBtn = document.getElementById('completeErrandBtn');
                    const cancelRequestBtn = document.getElementById('cancelRequestBtn');
                    const pendingCancelBadge = document.getElementById('pendingCancelBadge');

                    if (Number(errand.cancel_requested_by_tasker) === 0) {
                        completeErrandBtn.style.display = 'flex';
                        cancelRequestBtn.style.display = 'flex';
                        pendingCancelBadge.style.display = 'none';

                        completeErrandBtn.addEventListener('click', async function() {
                            const customTitle = 'Mark Errand as Finished';
                            const customMessage = 'Are you sure you want to mark errand #' + errand.id + ' ("' + errand.title + '") as finished? The errand will then await client confirmation and cash payment.';
                            const isConfirmed = await showConfirmation(customTitle, customMessage);

                            if (!isConfirmed) return;

                            try {
                                const response = await fetch('/api/tasker/errands/' + errand.id + '/awaiting-confirmation', {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    credentials: 'same-origin'
                                });

                                const result = await response.json();

                                if (!response.ok || !result.success) {
                                    throw new Error(
                                        result.message || 'Failed to mark errand as finished.'
                                    );
                                }

                                sessionStorage.setItem(
                                    'success_message',
                                    result.message || 'Errand marked as finished.'
                                );

                                window.location.href = window.location.href;

                            } catch (error) {
                                console.error('Error:', error);
                                displayGlobalError(error.message);
                            }
                        });

                        cancelRequestBtn.addEventListener('click', async function() {
                            const customTitle = 'Confirm Cancellation Request';
                            const customMessage = 'Are you sure you want to request cancellation of errand #' + errand.id + ' ("' + errand.title + '")? The request will be sent to the Admin for review.';

                            const isConfirmed = await showConfirmation(customTitle, customMessage);

                            if (!isConfirmed) return;

                            try {
                                const response = await fetch('/api/tasker/errands/' + errand.id + '/request-cancel', {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    credentials: 'same-origin'
                                });

                                const result = await response.json();

                                if (!response.ok || !result.success) {
                                    throw new Error(
                                        result.message || 'Failed to submit cancellation request.'
                                    );
                                }

                                sessionStorage.setItem(
                                    'success_message',
                                    result.message || 'Cancellation request submitted successfully.'
                                );

                                window.location.href = window.location.href;

                            } catch (error) {
                                console.error('Error:', error);
                                displayGlobalError(error.message);
                            }
                        });

                    } else {
                        completeErrandBtn.style.display = 'none';
                        cancelRequestBtn.style.display = 'none';
                        pendingCancelBadge.style.display = 'flex';
                    }

                } else if (errand.raw_status === 'awaiting_confirmation') {
                    const awaitingClientConfirmationBadge = document.getElementById('awaitingClientConfirmationBadge');
                    awaitingClientConfirmationBadge.style.display = 'flex';
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

                        try {
                            const response = await fetch('/api/tasker/history/' + errand.id + '/delete', {
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
                            window.location.href = '{{ route("tasker.history") }}';

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
