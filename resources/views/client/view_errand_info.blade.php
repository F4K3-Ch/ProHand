@extends('layouts.app')

@section('title', 'Client View Errand')

@section('sidebar')
    @include('layouts.sidebar.client')
@endsection

@section('content')
    <a href="{{ route('client.dashboard') }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <main class="view-main" data-errand-id="{{ $id }}">
        <div class="info-container">
            <div class="errand-details-container">
                <h1>Errand Details</h1>

                <div class="error-success-message">
                    <p class="success" id="flashMessageText" style="display: none;"></p>
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
                    <label>Contact</label>
                    <div id="errand-contact" class="text-area">Loading...</div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Location</label>
                        <span id="errand-location">Loading...</span>
                    </div>

                    <div class="info-box">
                        <label>Fee</label>
                        <span id="errand-fee">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Created At</label>
                        <span id="errand-created-at">Loading...</span>
                    </div>

                    <div class="info-box">
                        <label>Deadline</label>
                        <span id="errand-deadline">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Accepted At</label>
                        <span id="errand-accepted-at">Loading...</span>
                    </div>

                    <div class="info-box">
                        <label>In Process At</label>
                        <span id="errand-in-process-at">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Finished At</label>
                        <span id="errand-finished-at">Loading...</span>
                    </div>
                    
                    <div class="info-box">
                        <label>Status</label>
                        <span id="errand-status" class="status-field">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Attachment</label>
                        <span id="errand-attachment">Loading...</span>
                    </div>
                    
                    <div class="info-box">
                        <label>Proof Of Payment</label>
                        <span id="errand-payment-proof">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const token = localStorage.getItem('token');
    const mainElement = document.querySelector('.view-main');
    const errandId = mainElement?.dataset.errandId;

    if (!token || !errandId) {
        return;
    }

    function formatDate(date) {
        if (!date) {
            return 'Not set';
        }

        const value = new Date(date);

        if (isNaN(value.getTime())) {
            return 'Not set';
        }

        return value.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        }) + ' ' + value.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function formatCategory(category) {
        if (!category) {
            return 'Not set';
        }

        return category
            .replace(/_/g, ' ')
            .replace(/\b\w/g, char => char.toUpperCase());
    }

    function formatStatus(status) {
        if (!status) {
            return 'Not set';
        }

        return status
            .replace(/_/g, ' ')
            .replace(/\b\w/g, char => char.toUpperCase());
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function setText(id, value) {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value;
        }
    }

    try {
        const response = await fetch(
            `/api/client/errands/${encodeURIComponent(errandId)}`,
            {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            }
        );

        const data = await response.json();

        if (!response.ok) {
            throw data;
        }

        const errand = data.errand;

        if (!errand) {
            throw new Error('Errand data was not returned.');
        }

        setText('errand-id', errand.id);
        setText('errand-title', errand.title);
        setText('errand-category', formatCategory(errand.category));
        setText('errand-description', errand.description || 'No description');
        setText('errand-contact', errand.contact || 'No contact information');
        setText('errand-location', errand.location || 'Not set');

        setText(
            'errand-fee',
            '₱' + Number(errand.fee || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        );

        setText('errand-created-at', formatDate(errand.created_at));
        setText('errand-deadline', formatDate(errand.deadline));
        setText('errand-accepted-at', formatDate(errand.accepted_at));
        setText('errand-in-process-at', formatDate(errand.in_process_at));
        setText('errand-finished-at', formatDate(errand.completed_at));

        const statusElement = document.getElementById('errand-status');

        if (statusElement) {
            statusElement.textContent = formatStatus(errand.status);

            if (errand.status) {
                statusElement.classList.add(
                    'status-' + errand.status
                );
            }
        }

        const attachmentElement =
            document.getElementById('errand-attachment');

        if (attachmentElement) {
            if (errand.attachment) {
                const attachmentUrl =
                    "{{ asset('storage') }}/" +
                    errand.attachment.file_path;

                attachmentElement.innerHTML = `
                    <a href="${attachmentUrl}" target="_blank">
                        ${escapeHtml(errand.attachment.original_name)}
                    </a>
                `;
            } else {
                attachmentElement.textContent = 'No attachment';
            }
        }

        const paymentElement =
            document.getElementById('errand-payment-proof');

        if (paymentElement) {
            if (errand.payment_proof) {
                const paymentUrl =
                    "{{ asset('storage') }}/" +
                    errand.payment_proof.file_path;

                paymentElement.innerHTML = `
                    <a href="${paymentUrl}" target="_blank">
                        ${escapeHtml(errand.payment_proof.original_name)}
                    </a>
                `;
            } else {
                paymentElement.textContent = 'Not set';
            }
        }

    } catch (error) {
        console.error('Failed to load errand:', error);

        setText('errand-id', 'Not available');
        setText('errand-title', 'Unable to load errand.');
        setText('errand-category', 'Not available');
        setText('errand-description', 'Not available');
        setText('errand-contact', 'Not available');
        setText('errand-location', 'Not available');
        setText('errand-fee', 'Not available');
        setText('errand-created-at', 'Not available');
        setText('errand-deadline', 'Not available');
        setText('errand-accepted-at', 'Not available');
        setText('errand-in-process-at', 'Not available');
        setText('errand-finished-at', 'Not available');
        setText('errand-status', 'Not available');
        setText('errand-attachment', 'Not available');
        setText('errand-payment-proof', 'Not available');
    }
});
</script>
@endpush