@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('sidebar')
    @include('layouts.sidebar.admin')
@endsection

@section('content')
    <main class="main">
        <div class="fixed-wrapper">
            <header class="header"> 
                <div class="header-left">
                    <div class="logo">
                        <img src="{{ asset('img/ProHand(Adapt).png') }}" alt="ProHand Logo" class="logo-img">
                        <div class="title">
                            <h1>Pro<span>Hand</span></h1>
                            <h2>Dashboard</h2>
                        </div>

                        <label for="sidebar-toggle-check" class="sidebar-toggle-btn" aria-label="Toggle Navigation Menu">
                            <i class="fa-solid fa-bars"></i>
                        </label>
                    </div>
                </div>
                <div class="header-right">
                    <div class="search-wrapper">
                        <input type="text" name="search" value="" placeholder="Search">
                    </div>
                    <button type="submit" class="btn-search">Search</button>
                    </form>
                </div>
            </header>
        </div>

        <div class="dashboard">
            <div class="error-success-message" id="flashMessageContainer" style="display: none;">
                <p class="success" id="flashMessageText"></p>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const token = localStorage.getItem('token');

            if (!token) {
                return;
            }

            try {
                const response = await fetch('/api/admin/dashboard', {
                    method: 'GET',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw data;
                }

                const container = document.getElementById('flashMessageContainer');
                const text = document.getElementById('flashMessageText');

                if (container && text && data.message) {
                    text.textContent = data.message;
                    container.style.display = 'block';

                    setTimeout(() => {
                        container.style.display = 'none';
                    }, 3000);
                }
            } catch (error) {
                console.error('Dashboard error:', error);
            }
        });
    </script>
@endpush