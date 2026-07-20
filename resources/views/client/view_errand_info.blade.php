@extends('layouts.app')

@section('title', 'ProHand')

@section('content')
    <a href="{{ $back_url ?? route('client.dashboard') }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <main class="view-main">
        <div class="info-container">
            <div class="errand-details-container">
                <h1>Errand Details</h1>

                @if (session('success') || session('error'))
                    <div class="view-main-error-success-message">
                        @if (session('success'))
                            <p class="success">{{ session('success') }}</p>
                        @elseif (session('error'))
                            <p class="error">{{ session('error') }}</p>
                        @endif
                    </div>
                @endif

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Errand ID</label>
                        <input type="text" readonly value="{{ $errand->id }}">
                    </div>
                    <div class="info-box">
                        <label>Title</label>
                        <input type="text" readonly value="{{ $errand->title }}">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Category</label>
                        <input type="text" readonly value="{{ $errand->category }}">
                    </div>
                    <div class="info-box">
                        <label>Expected Hours</label>
                        <input type="text" readonly value="{{ $errand->expected_hours }}">
                    </div>
                </div>

                <div class="info-box">
                    <label>Description</label>
                    <textarea readonly>{{ $errand->description }}</textarea>
                </div>

                <div class="info-box">
                    <label>Contact</label>
                    <textarea readonly>{{ $errand->contact }}</textarea>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Location</label>
                        <input type="text" readonly value="{{ $errand->location }}">
                    </div>
                    <div class="info-box">
                        <label>Fee</label>
                        <input type="text" readonly value="₱{{ number_format($errand->fee, 2) }}">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Created At</label>
                        <input type="text" readonly value="{{ $errand->created_at ? $errand->created_at->timezone('Asia/Manila')->format('F j, Y g:i A') : 'Not set' }}">
                    </div>
                    <div class="info-box">
                        <label>Deadline</label>
                        <input type="text" readonly value="{{ $errand->deadline ? $errand->deadline->format('F j, Y g:i A') : 'Not set' }}">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Accepted At</label>
                        <input type="text" readonly value="{{ $errand->accepted_at ? $errand->accepted_at->format('F j, Y g:i A') : 'Not set' }}">
                    </div>
                    <div class="info-box">
                        <label>In Process At</label>
                        <input type="text" readonly value="{{ $errand->in_process_at ? $errand->in_process_at->format('F j, Y g:i A') : 'Not set' }}">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Finished At</label>
                        <input type="text" readonly value="{{ $errand->completed_at ? $errand->completed_at->format('F j, Y g:i A') : ($errand->failed_at ? $errand->failed_at->format('F j, Y g:i A') : 'Not set') }}">
                    </div>
                    <div class="info-box">
                        <label>Status</label>
                        <input type="text" readonly class="status-input-field status-{{ $errand->status }}" value="{{ ucwords(str_replace('_', ' ', $errand->status)) }}">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Attachment</label>
                        <div class="attachment-item">
                            @if($errand->attachment)
                                <a href="{{ asset('storage/' . $errand->attachment->file_path) }}" target="_blank" class="attachment-name">
                                    <i class="fa-solid fa-file-lines"></i> {{ $errand->attachment->original_name }}
                                </a>
                                
                                <a href="{{ route('errand.download', $errand->attachment->id) }}" class="btn-download">
                                    Download
                                </a>
                            @else
                                <p>None</p>
                            @endif
                        </div>
                    </div>

                    @if($errand->paymentProof && $errand->status === 'Pending')
                        <div class="info-box">
                            <label>Proof of Payment</label>
                            <div class="attachment-item">
                                <a href="{{ asset('storage/' . $errand->paymentProof->file_path) }}" target="_blank" class="attachment-name">
                                    <i class="fa-solid fa-file-lines"></i> {{ $errand->paymentProof->original_name }}
                                </a>
                                
                                <a href="{{ route('errand.payment-proof.download', $errand->paymentProof->id) }}" class="btn-download">
                                    Download
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="info-bot-btn">
                    @if($errand->status === 'pending')
                        <!-- Your forms go here -->
                    @elseif($errand->status === 'pending_payment')
                        <!-- Your forms go here -->
                    @endif
                </div>

            </div>
        </div>
    </main>
@endsection