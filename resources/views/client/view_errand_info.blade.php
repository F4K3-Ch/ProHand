@extends('layouts.app')

@section('title', 'View Errand')

@section('sidebar')
    @include('layouts.sidebar.client')
@endsection

@section('content')
    <a href="{{ route(request('from', 'client.dashboard')) }}" class="btn-back">
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
                        <label><i class="fa-solid fa-location-dot"></i> Location</label>
                        <div id="errand-location" class="text-area">Loading...</div>
                    </div>

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

                <div class="users-container" id="users-container" style="display:none;">
                    <div class="user-details-container">
                        <h2>Accepted By</h2>

                        <div class="user-main-content">
                            <div class="user-profile-card-layout">
                                <div class="profile-photo-section">
                                    <div class="photo-wrapper">
                                        <img id="tasker-profile-photo" class="profile-preview-photo" src="" alt="Profile Picture" style="display:none;">
                                        <div id="profile-photo-spinner" class="circle-spinner"></div>
                                    </div>
                                </div>

                                <div class="user-info-rows">
                                    <div class="detail-row">
                                        <span>User ID</span>
                                        <span id="tasker-id" class="errand-val">Loading...</span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Email</span>
                                        <span id="tasker-email" class="errand-val">Loading...</span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Name</span>
                                        <span id="tasker-name" class="errand-val">Loading...</span>
                                    </div>
                                    <div class="detail-row">
                                        <span>Contact Number</span>
                                        <span id="tasker-contact-number" class="errand-val">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="gcash-payment-container" id="gcashPaymentContainer" style="display:none;">
                    <h1>
                        <i class="fa-solid fa-wallet"></i>
                        Secure Payment
                    </h1>

                    <div id="gcashPaymentForm" class="split-payment-container" style="display:none;">
                        <div class="payment-action-panel">
                            <a href="{{ asset('img/gcash-full-qr.jpg') }}" target="_blank" title="Click to view full image">
                                <img src="{{ asset('img/gcash-qr.jpg') }}" alt="GCash Merchant QR Code" class="payment-qr-img">
                            </a>
                        </div>

                        <div class="payment-info-panel">
                            <div class="payment-text-details">
                                <p class="alert-text">
                                    <strong>GCash Payment Instructions:</strong><br>
                                    Scan the QR code or send the exact amount of
                                    <strong id="gcashAmount">Loading...</strong>
                                    to our GCash account to secure your tasker.
                                </p>

                                <p class="merchant-number">
                                    <i class="fa-solid fa-mobile-screen"></i>
                                    0994-221-5875
                                </p>

                                <span class="qr-hint-text">
                                    <i class="fa-solid fa-qrcode"></i>
                                    Tip: Save the QR to your gallery to scan via GCash App.
                                </span>
                            </div>

                            <div class="info-box create-errand-input-box payment-right-upload">
                                <label>Upload Receipt Image</label>
                                <input type="file" id="paymentProof" accept=".jpg,.jpeg,.png">
                                <small>Only PNG, JPG, or JPEG allowed. Max: 10 MB</small>
                            </div>

                            <button type="button" id="submitPaymentBtn" class="btn-submit-proof">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                Submit Payment
                                <div class="circle-spinner" id="paymentSpinner" style="display:none;"></div>
                            </button>
                        </div>
                    </div>

                    <div id="gcashReceipt" class="payment-verification-panel" style="display:none;">
                        <div class="payment-status-header">
                            <i class="fa-solid fa-spinner fa-spin status-icon-orange"></i>
                            <h2 class="status-title-orange">Pending Admin Verification</h2>
                        </div>

                        <p class="payment-status-message">
                            <strong>Please wait until the admin checks your uploaded receipt.</strong>
                            Once verified, your tasker will be notified immediately.
                        </p>

                        <div class="errand-attachment-section">
                            <label><i class="fa-solid fa-file-invoice-dollar"></i> Your Uploaded Receipt</label>

                            <div id="gcash-receipt-attachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" title="Preview File">
                                        <i class="fa-solid fa-file"></i>
                                        <span class="attachment-filename"></span>
                                    </a>
                                    <a href="#" class="btn-download" download>
                                        <i class="fa-solid fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="completedPaymentProofPanel" class="payment-verification-panel" style="display:none;">
                        <div class="payment-status-header">
                            <i class="fa-solid fa-circle-check status-icon-orange"></i>
                            <h2 class="status-title-orange">Payment Receipt</h2>
                        </div>

                        <p class="payment-status-message">
                            <strong>This errand has been completed.</strong>
                            The original payment receipt has been preserved in the errand history.
                        </p>

                        <div class="errand-attachment-section">
                            <label>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Original Payment Receipt
                            </label>

                            <div id="completedPaymentProofAttachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" id="completedPaymentProofPreview" title="Preview Payment Receipt">
                                        <i class="fa-solid fa-file"></i>
                                        <span id="completedPaymentProofFilename"></span>
                                    </a>

                                    <a href="#" class="btn-download" id="completedPaymentProofDownload" download>
                                        <i class="fa-solid fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="failedRefundProofPanel" class="payment-verification-panel" style="display:none;">
                        <div class="payment-status-header">
                            <i class="fa-solid fa-money-bill-transfer status-icon-orange"></i>
                            <h2 class="status-title-orange">Payment & Refund Receipts</h2>
                        </div>

                        <p class="payment-status-message">
                            <strong>This errand has been marked as failed and refunded.</strong>
                            The original payment receipt and refund receipt have been preserved in the errand history.
                        </p>

                        <div class="errand-attachment-section">
                            <label>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Original Payment Receipt
                            </label>

                            <div id="failedPaymentProofAttachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" id="failedPaymentProofPreview" title="Preview Payment Receipt">
                                        <i class="fa-solid fa-file"></i>
                                        <span id="failedPaymentProofFilename"></span>
                                    </a>

                                    <a href="#" class="btn-download" id="failedPaymentProofDownload" download>
                                        <i class="fa-solid fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="errand-attachment-section">
                            <label>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Refund Receipt
                            </label>

                            <div id="failedRefundProofAttachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" id="failedRefundProofPreview" title="Preview Refund Receipt">
                                        <i class="fa-solid fa-file"></i>
                                        <span id="failedRefundProofFilename"></span>
                                    </a>

                                    <a href="#" class="btn-download" id="failedRefundProofDownload" download>
                                        <i class="fa-solid fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-bot-btn">
                    <a href="#" id="editErrandBtn" class="btn-edit-errand" style="display:none;">
                        <i class="fa-solid fa-pen"></i> Edit Errand
                    </a>

                    <button type="button" id="deleteErrandBtn" class="btn-delete" style="display:none;">
                        <i class="fa-solid fa-trash"></i> Delete Errand
                    </button>

                    <button type="button" id="cancelAcceptedBtn" class="btn-cancel" style="display:none;">
                        <i class="fa-solid fa-ban"></i> Cancel
                    </button>

                    <div class="badge-button-column" id="inProgressBadgeContainer" style="display:none;">
                        <span class="descriptive-status-badge" id="performingBadge" style="display:none;">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                            Tasker is currently working on this errand...
                        </span>

                        <button type="button" id="cancelRequestBtn" class="btn-cancel" style="display:none;">
                            <i class="fa-solid fa-hand-holding-hand"></i> Request Cancel
                        </button>

                        <span class="descriptive-status-badge" id="pendingCancelBadge" style="display:none;">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                            Waiting for Admin Decision on your Cancellation request...
                        </span>
                    </div>

                    <button type="button" id="confirmCompletionBtn" class="btn-confirm-completion" style="display:none;">
                        <i class="fa-solid fa-check"></i> Confirm Completion
                    </button>

                    <button type="button" id="disputeErrandBtn" class="btn-dispute" style="display:none;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Dispute
                    </button>

                    <span class="descriptive-status-badge" id="disputeBadge" style="display:none;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        You have raised a dispute for this task. Our admin team is currently reviewing the submitted details and will reach out to you shortly.
                    </span>
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
                const response = await fetch('/api/client/errands/' + errandId, {
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
                        '/client/edit-errand/' + errand.id +
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
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'accepted') {
                    const cancelAcceptedBtn = document.getElementById('cancelAcceptedBtn');
                    const gcashPaymentContainer = document.getElementById('gcashPaymentContainer');
                    const gcashPaymentForm = document.getElementById('gcashPaymentForm');
                    const gcashReceipt = document.getElementById('gcashReceipt');
                    const paymentProof = document.getElementById('paymentProof');
                    const submitPaymentBtn = document.getElementById('submitPaymentBtn');
                    const paymentSpinner = document.getElementById('paymentSpinner');

                    cancelAcceptedBtn.style.display = 'flex';

                    if (errand.payment && errand.payment.method.toLowerCase() === 'gcash') {
                        gcashPaymentContainer.style.display = 'flex';

                        if (errand.payment.proof) {
                            gcashPaymentForm.style.display = 'none';
                            gcashReceipt.style.display = 'flex';

                            const receiptItem = document.querySelector('#gcash-receipt-attachment .attachment-item');
                            receiptItem.querySelector('.attachment-filename').textContent = errand.payment.proof.name;
                            receiptItem.querySelector('.attachment-name').href = errand.payment.proof.url;

                            const downloadBtn = receiptItem.querySelector('.btn-download');
                            downloadBtn.href = errand.payment.proof.url;
                            downloadBtn.setAttribute('download', errand.payment.proof.name);
                        } else {
                            gcashPaymentForm.style.display = 'flex';
                            gcashReceipt.style.display = 'none';
                            document.getElementById('gcashAmount').textContent = errand.payment.amount;
                        }
                    }

                    submitPaymentBtn.addEventListener('click', async function() {
                        if (!paymentProof.files.length) {
                            displayGlobalError('Please select a payment proof image.');
                            return;
                        }

                        const formData = new FormData();
                        formData.append('payment_proof', paymentProof.files[0]);

                        submitPaymentBtn.disabled = true;
                        paymentSpinner.style.display = 'inline-block';

                        try {
                            const response = await fetch('/api/client/errands/' + errand.id + '/payment-proof', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN': origin
                                },
                                body: formData,
                                credentials: 'same-origin'
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to upload payment proof.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        } finally {
                            submitPaymentBtn.disabled = false;
                            paymentSpinner.style.display = 'none';
                        }
                    });

                    cancelAcceptedBtn.addEventListener('click', async function() {
                        const isConfirmed = await showConfirmation(
                            'Confirm Cancellation',
                            'Are you sure you want to cancel this errand? It will return to "Pending" status.'
                        );

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch('/api/client/errands/' + errand.id + '/cancel', {
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
                                throw new Error(result.message || 'Failed to cancel errand.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'in_progress') {
                    const inProgressBadgeContainer = document.getElementById('inProgressBadgeContainer');
                    inProgressBadgeContainer.style.display = 'flex';
                    if (Number(errand.cancel_requested_by_client) === 0) {
                        document.getElementById('performingBadge').style.display = 'flex';
                        const cancelRequestBtn = document.getElementById('cancelRequestBtn');
                        cancelRequestBtn.style.display = 'flex';

                        cancelRequestBtn.addEventListener('click', async function() {
                            const customTitle = 'Confirm Cancellation Request';
                            const customMessage = 'Are you sure you want to send a cancellation request to the Admin? It will return to "Pending" status and this action cannot be undone until processed.';
                            const isConfirmed = await showConfirmation(customTitle, customMessage);
                            
                            if(!isConfirmed) return;

                            try {
                                const response = await fetch('/api/client/errands/' + errand.id + '/request-cancel', {
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

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch('/api/client/errands/' + errand.id + '/completed', {
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
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("client.history") }}';

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                    disputeErrandBtn.addEventListener('click', async function() {
                        const customTitle = 'Dispute Errand';
                        const customMessage = 'Are you sure you want to dispute errand #' + errand.id + ' ("' + errand.title + '")? This action will notify the Admin for review.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch('/api/client/errands/' + errand.id + '/dispute', {
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
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'dispute') {
                    const disputeBadge = document.getElementById('disputeBadge');
                    disputeBadge.style.display = 'flex';
                } else if (errand.raw_status === 'completed') {
                    const deleteBtn = document.getElementById('deleteErrandBtn');
                    const gcashPaymentContainer = document.getElementById('gcashPaymentContainer');
                    const completedPaymentProofPanel = document.getElementById('completedPaymentProofPanel');

                    deleteBtn.style.display = 'flex';

                    if (
                        errand.payment &&
                        errand.payment.method &&
                        errand.payment.method.toLowerCase() === 'gcash'
                    ) {
                        gcashPaymentContainer.style.display = 'flex';

                        if (errand.payment.proof) {
                            completedPaymentProofPanel.style.display = 'flex';

                            document.getElementById('completedPaymentProofFilename').textContent =
                                errand.payment.proof.name;

                            document.getElementById('completedPaymentProofPreview').href =
                                errand.payment.proof.url;

                            const paymentDownload =
                                document.getElementById('completedPaymentProofDownload');

                            paymentDownload.href = errand.payment.proof.url;
                            paymentDownload.setAttribute(
                                'download',
                                errand.payment.proof.name
                            );
                        }
                    }

                    deleteBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Deletion';
                        const customMessage =
                            'Are you sure you want to delete errand #' +
                            errand.id +
                            ' ("' +
                            errand.title +
                            '")? It will be deleted permanently.';

                        const isConfirmed = await showConfirmation(
                            customTitle,
                            customMessage
                        );

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch(
                                '/api/client/history/' + errand.id + '/delete',
                                {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN':
                                            document.querySelector('meta[name="csrf-token"]')
                                                ?.getAttribute('content') || '',
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    credentials: 'same-origin'
                                }
                            );

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem(
                                'success_message',
                                result.message
                            );

                            window.location.href = '{{ route("client.history") }}';

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'failed') {
                    const deleteBtn = document.getElementById('deleteErrandBtn');
                    const gcashPaymentContainer = document.getElementById('gcashPaymentContainer');
                    const failedRefundProofPanel = document.getElementById('failedRefundProofPanel');

                    deleteBtn.style.display = 'flex';

                    if (
                        errand.payment &&
                        errand.payment.method &&
                        errand.payment.method.toLowerCase() === 'gcash'
                    ) {
                        gcashPaymentContainer.style.display = 'flex';

                        const paymentProof = errand.payment.proof;
                        const refundProof = errand.payment.refund_proof;

                        if (paymentProof || refundProof) {
                            failedRefundProofPanel.style.display = 'flex';
                        }

                        if (paymentProof) {
                            document.getElementById('failedPaymentProofFilename').textContent =
                                paymentProof.name;

                            document.getElementById('failedPaymentProofPreview').href =
                                paymentProof.url;

                            const paymentDownload =
                                document.getElementById('failedPaymentProofDownload');

                            paymentDownload.href = paymentProof.url;
                            paymentDownload.setAttribute(
                                'download',
                                paymentProof.name
                            );
                        }

                        if (refundProof) {
                            document.getElementById('failedRefundProofFilename').textContent =
                                refundProof.name;

                            document.getElementById('failedRefundProofPreview').href =
                                refundProof.url;

                            const refundDownload =
                                document.getElementById('failedRefundProofDownload');

                            refundDownload.href = refundProof.url;
                            refundDownload.setAttribute(
                                'download',
                                refundProof.name
                            );
                        }
                    }

                    deleteBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Deletion';
                        const customMessage =
                            'Are you sure you want to delete errand #' +
                            errand.id +
                            ' ("' +
                            errand.title +
                            '")? It will be deleted permanently.';

                        const isConfirmed = await showConfirmation(
                            customTitle,
                            customMessage
                        );

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch(
                                '/api/client/history/' + errand.id + '/delete',
                                {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN':
                                            document.querySelector('meta[name="csrf-token"]')
                                                ?.getAttribute('content') || '',
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    credentials: 'same-origin'
                                }
                            );

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem(
                                'success_message',
                                result.message
                            );

                            window.location.href = '{{ route("client.history") }}';

                        } catch (error) {
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