@extends('layouts.app')

@section('title', 'Client Dashboard')

@section('sidebar')
    @include('layouts.sidebar.client')
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

            <div class="contents">
                <section class="totals">
                    <h3>Errand Status Metrics</h3>
                    <div class="total-cards-container">
                        <div class="total-card bg-pending">
                            <div class="card-header">
                                <span class="status-circle circle-pending"></span>
                                <h3>Pending</h3>
                            </div>
                            <p id="metric-pending">0</p>
                        </div>
                        <div class="total-card bg-accepted">
                            <div class="card-header">
                                <span class="status-circle circle-accepted"></span>
                                <h3>Pending Payment</h3>
                            </div>
                            <p id="metric-pending-payment">0</p>
                        </div>
                        <div class="total-card bg-process">
                            <div class="card-header">
                                <span class="status-circle circle-process"></span>
                                <h3>In Process</h3>
                            </div>
                            <p id="metric-in-process">0</p>
                        </div>
                        <div class="total-card bg-completed">
                            <div class="card-header">
                                <span class="status-circle circle-complete"></span>
                                <h3>Pending Completion</h3>
                            </div>
                            <p id="metric-pending-completion">0</p>
                        </div>
                        <div class="total-card bg-failed">
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Pending Failured</h3>
                            </div>
                            <p id="metric-pending-failure">0</p>
                        </div>
                        <div class="total-card bg-failed">
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Dispute</h3>
                            </div>
                            <p id="metric-dispute">0</p>
                        </div>
                        <div class="total-card bg-total">
                            <div class="card-header">
                                <span class="status-circle circle-total"></span>
                                <h3>Total Active Errands</h3>
                            </div>
                            <p id="metric-total">0</p>
                        </div>
                    </div>
                </section>

                <div class="client-errands-table">
                    <div class="table-header-controls">
                        <h4>My Errands</h4>
                        <div class="rows-per-page">
                            <label for="perPageSelect"><h4>Show: </h4></label>
                            <select id="perPageSelect" class="form-control">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Errand ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Created At</th>
                                    <th>Deadline</th>
                                    <th>Fee</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="errands-table-body">
                                <tr><td class="no-found" colspan="8">Loading errands...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="pagination-container" class="pagination-container">

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

            if (!token) {
                return;
            }

            try {
                const response = await fetch('/api/client/dashboard', {
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