@extends('layouts.app')

@section('title', 'Create Errand')

@section('sidebar')
    @include('layouts.sidebar.client')
@endsection

@section('content')
    <a href="{{ route('client.dashboard') }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <main class="view-main">
        <div class="create-errand-container">
            <h1>Create Errand</h1>

            <div class="view-main-error-success-message">
                <p id="errorElement" class="error" style="display: none;"></p>
            </div>

            <form id="create-errand-form" enctype="multipart/form-data">
                @csrf

                <div class="create-errand-input-box">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" required>
                </div>

                <div class="create-errand-input-box">
                    <label for="category">Category</label>
                    <select name="category" id="category" required>
                        <option value="" disabled selected>Select a category</option>
                        <option value="delivery">Delivery</option>
                        <option value="moving">Moving</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="repair">Repair</option>
                        <option value="grocery">Grocery</option>
                        <option value="laundry">Laundry</option>
                        <option value="project">Project</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="create-errand-input-box">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" required></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label for="location">Location</label>
                    <input type="text" name="location" id="location" required>
                </div>

                <div class="create-errand-input-box">
                    <label for="deadline">Deadline</label>
                    <input type="text" name="deadline" id="deadline" required>
                </div>

                <div class="create-errand-input-box">
                    <label for="fee">Fee (₱)</label>
                    <input type="number" step="0.01" min="0" name="fee" id="fee" required>
                </div>

                <div class="create-errand-input-box">
                    <label for="contact">Contact</label>
                    <textarea name="contact" id="contact" placeholder="Phone, email, social media..." required></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label for="attachment">Attachment (Optional)</label>
                    <input type="file" name="attachment" id="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">
                    <small>Only 1 file allowed. Maximum size: 10 MB</small>
                </div>

                <button type="submit" class="btn" id="createErrandButton">
                    Create Errand
                    <div class="circle-spinner" id="errandSpinner" style="display: none;"></div>
                </button>
            </form>
        </div>
    </main>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr('#deadline', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i:s',
            altInput: true,
            altFormat: 'F j, Y h:i K',
            minDate: 'today'
        });

        const form = document.getElementById('create-errand-form');
        const fileInput = document.getElementById('attachment');
        const errorElement = document.getElementById('errorElement');
        const errandSpinner = document.getElementById('errandSpinner');
        const createErrandButton = document.getElementById('createErrandButton');

        fileInput.addEventListener('change', function () {
            const maxSize = 10 * 1024 * 1024;

            if (this.files.length > 0 && this.files[0].size > maxSize) {
                errorElement.textContent = 'This file is too large. Please choose a file smaller than 10MB.';
                errorElement.style.display = 'block';
                this.value = '';
                return;
            }

            errorElement.textContent = '';
            errorElement.style.display = 'none';
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            errandSpinner.style.display = 'inline-block';
            createErrandButton.disabled = true;
            errorElement.textContent = '';
            errorElement.style.display = 'none';

            const formData = new FormData(form);

            try {
                const response = await fetch('/api/client/create', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'X-API-KEY': apiKey,
                        'X-MY-ORIGIN' : origin
                    },
                    credentials: 'same-origin',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok) {
                    throw data;
                }

                sessionStorage.setItem('success_message', data.message);
                window.location.href = "{{ route('client.dashboard') }}";
            } catch (err) {
                let errorMessage = 'An unexpected error occurred.';

                if (err.errors) {
                    const firstKey = Object.keys(err.errors)[0];

                    if (firstKey && err.errors[firstKey]?.length) {
                        errorMessage = err.errors[firstKey][0];
                    }
                } else if (err.message) {
                    errorMessage = err.message;
                } else if (err.error) {
                    errorMessage = err.error;
                }

                errorElement.textContent = errorMessage;
                errorElement.style.display = 'block';
            } finally {
                errandSpinner.style.display = 'none';
                createErrandButton.disabled = false;
            }
        });
    </script>
@endpush