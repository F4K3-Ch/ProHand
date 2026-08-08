@extends('layouts.app')

@section('title', 'ProHand')

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

            <div class="error-success-message">
                <p id="errorElement" class="error" style="display: none;"></p>
            </div>

            <form id="create-errand-form">
                <div class="create-errand-input-box">
                    <label>Title</label>
                    <input type="text" name="title" id="title" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Category</label>
                    <select name="category" id="category" required>
                        <option value="" disabled selected>Select a category</option>
                        <option value="delivery">Delivery</option>
                        <option value="moving">Moving</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="repair">Repair</option>
                        <option value="grocery">Grocery</option>
                        <option value="laundry">Laundry</option>
                        <option value="project">Project</option>
                        <option value="others">Others</option>
                    </select>
                </div>

                <div class="create-errand-input-box">
                    <label>Description</label>
                    <textarea name="description" id="description" required></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Location</label>
                    <input type="text" name="location" id="location" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Deadline</label>
                    <input type="text" name="deadline" id="deadline" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Fee (₱)</label>
                    <input type="number" step="0.01" name="fee" id="fee" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Contact</label>
                    <textarea name="contact" id="contact" placeholder="Phone, email, social media..." required></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Attachment (Optional)</label>
                    <input type="file" name="attachment" id="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">
                    <small>Only 1 file allowed. Maximum size: 10 MB</small>
                </div>

                <button type="submit" class="btn">
                    Create Errand <div class="circle-spinner" id="errandSpinner" style="display: none;"></div>
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
        flatpickr("#deadline", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:s", 
            altInput: true,            
            altFormat: "F j, Y h:i K", 
            minDate: "today"
        });

        const fileInput = document.getElementById('attachment');
        const errorElement = document.getElementById('errorElement');
        
        fileInput.addEventListener('change', function() {
            const maxSize = 10 * 1024 * 1024; 
            if (this.files.length > 0) {
                const fileSize = this.files[0].size;
                if (fileSize > maxSize) {
                    errorElement.textContent = 'This file is too large! Please choose a file smaller than 10MB.';
                    errorElement.style.display = 'block';
                    this.value = '';
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                }
            }
        });

        document.getElementById('create-errand-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const token = localStorage.getItem('token');
            const errandSpinner = document.getElementById('errandSpinner');

            errandSpinner.style.display = 'inline-block';
            errorElement.textContent = '';
            errorElement.style.display = 'none';

            const formData = new FormData(this);

            fetch('/api/client/tasks', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json().then(data => {
                if (!response.ok) {
                    throw data;
                }
                return data;
            }))
            .then(data => {
                sessionStorage.setItem('success_message', data.message);
                window.location.href = '/client/dashboard';
            })
            .catch(err => {
                let errorMessage = 'An unexpected error occurred.';
                
                if (err.error) {
                    errorMessage = err.error;
                } else if (err.message) {
                    errorMessage = err.message;
                } else if (err.errors) {
                    const firstKey = Object.keys(err.errors)[0];
                    errorMessage = err.errors[firstKey][0];
                }

                errorElement.textContent = errorMessage;
                errorElement.style.display = 'block';
            })
            .finally(() => {
                errandSpinner.style.display = 'none';
            });
        });
    </script>
@endpush